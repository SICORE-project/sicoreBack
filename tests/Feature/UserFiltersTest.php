<?php
namespace Tests\Feature;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['2026_07_04_000000_create_users_table', '2026_07_27_235253_create_roles_table', '2026_07_07_120005_create_enseignants_table'] as $migration) {
            (require database_path('migrations/'.$migration.'.php'))->up();
        }
        foreach (['ias', 'iefs', 'lieu_de_services'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id(); $t->string('code'); $t->string('libelle'); $t->string('type')->nullable();
                $t->unsignedBigInteger('ia_id')->nullable(); $t->unsignedBigInteger('ief_id')->nullable();
                $t->softDeletes(); $t->timestamps();
            });
        }
        foreach ([1, 2] as $id) {
            DB::table('ias')->insert(['id' => $id, 'code' => 'IA'.$id, 'libelle' => 'IA '.$id]);
            DB::table('iefs')->insert(['id' => $id, 'code' => 'IEF'.$id, 'libelle' => 'IEF '.$id, 'ia_id' => $id]);
            DB::table('lieu_de_services')->insert(['id' => $id, 'code' => 'E'.$id, 'libelle' => 'École '.$id, 'ia_id' => $id, 'ief_id' => $id]);
        }
        $role = Role::create(['nom' => 'Admin', 'slug' => 'super_admin']);
        Sanctum::actingAs(User::create(['nom' => 'Admin', 'prenom' => 'Test', 'email' => 'admin@example.test', 'password' => 'password123', 'role_id' => $role->id]));
        $teacher = Enseignant::create(['nom' => 'Ndiaye', 'prenom' => 'Awa', 'matricule' => '001234/F', 'ia_id' => 1, 'ief_id' => 1, 'lieu_service_id' => 1]);
        User::create(['nom' => 'Ndiaye', 'prenom' => 'Awa', 'email' => 'awa@example.test', 'password' => 'password123', 'enseignant_id' => $teacher->id]);
        User::create(['nom' => 'Diallo', 'prenom' => 'Ali', 'email' => 'ali@example.test', 'password' => 'password123', 'lieu_service_id' => 2]);
    }

    public function test_combined_filters_find_linked_teacher_and_return_location(): void
    {
        $this->getJson('/api/admin/users?'.http_build_query(['nom' => 'awa ndiaye', 'matricule' => '001234', 'ia_id' => 1, 'ief_id' => 1, 'etablissement_id' => 1]))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.matricule', '001234/F')->assertJsonPath('data.0.affectation.etablissement', 'École 1');
    }

    public function test_non_teacher_account_is_filtered_by_its_structure(): void
    {
        $this->getJson('/api/admin/users?ia_id=2&ief_id=2&etablissement_id=2')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.nom', 'Diallo');
    }

    public function test_filtering_happens_before_pagination(): void
    {
        for ($i = 0; $i < 12; $i++) User::create(['nom' => 'Recherche', 'prenom' => 'Test', 'email' => 'test'.$i.'@example.test', 'password' => 'password123']);
        $this->getJson('/api/admin/users?nom=Recherche&per_page=10&page=2')->assertOk()->assertJsonPath('meta.total', 12)->assertJsonCount(2, 'data');
    }

    public function test_options_follow_selected_ia_and_ief(): void
    {
        $this->getJson('/api/admin/users/filter-options')->assertOk()->assertJsonCount(2, 'data.ias')->assertJsonPath('data.iefs', []);
        $this->getJson('/api/admin/users/filter-options?ia_id=1')->assertOk()->assertJsonCount(1, 'data.iefs')->assertJsonPath('data.iefs.0.id', 1)->assertJsonPath('data.etablissements', []);
        $this->getJson('/api/admin/users/filter-options?ia_id=1&ief_id=1')->assertOk()->assertJsonCount(1, 'data.etablissements')->assertJsonPath('data.etablissements.0.id', 1);
    }

    public function test_inconsistent_hierarchy_and_missing_parents_are_rejected(): void
    {
        $this->getJson('/api/admin/users?ia_id=1&ief_id=2')->assertUnprocessable()->assertJsonValidationErrors('ief_id');
        $this->getJson('/api/admin/users?ia_id=1&ief_id=1&etablissement_id=2')->assertUnprocessable()->assertJsonValidationErrors('etablissement_id');
        $this->getJson('/api/admin/users/filter-options?ief_id=1')->assertUnprocessable()->assertJsonValidationErrors('ia_id');
    }

    public function test_options_require_user_read_permission(): void
    {
        $user = User::where('email', 'awa@example.test')->firstOrFail();
        Sanctum::actingAs($user);
        $this->getJson('/api/admin/users/filter-options')->assertForbidden();
    }
}
