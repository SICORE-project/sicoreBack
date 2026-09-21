<?php

namespace Tests\Feature;

use App\Models\Admin\Role;
use App\Models\Admin\User;
use App\Services\RecruitmentImporter;
use Database\Seeders\RecruitmentPermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecruitmentWorkflowTest extends TestCase
{
    private User $drh;

    private User $ia;

    private User $dage;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        foreach (['roles', 'permissions'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->string('nom');
                $t->string('slug');
                $t->boolean('est_actif')->default(true);
                $t->timestamps();
                if ($table === 'permissions') {
                    $t->string('groupe');
                    $t->string('module');
                    $t->string('action');
                }
            });
        }
        Schema::create('role_permission', function (Blueprint $t) {
            $t->integer('role_id');
            $t->integer('permission_id');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('prenom');
            $t->string('email');
            $t->string('password');
            $t->string('statut');
            foreach (['role_id', 'lieu_service_id', 'ia_id', 'ief_id'] as $field) {
                $t->integer($field)->nullable();
            }
            $t->timestamps();
            $t->softDeletes();
        });
        foreach (['ias', 'iefs', 'lieu_de_services'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->integer('ia_id')->nullable();
                $t->integer('ief_id')->nullable();
                $t->string('type')->nullable();
                $t->string('perimetre')->nullable();
                $t->string('libelle')->nullable();
                $t->boolean('est_actif')->default(true);
                $t->timestamps();
                $t->softDeletes();
            });
        }
        (require database_path('migrations/2026_07_07_120005_create_enseignants_table.php'))->up();
        (require database_path('migrations/2026_08_23_130000_add_payroll_engagement_type_to_enseignants.php'))->up();
        (require database_path('migrations/2026_09_22_000001_create_recruitment_workflow.php'))->up();
        DB::table('ias')->insert(['id' => 1]);
        DB::table('iefs')->insert(['id' => 1, 'ia_id' => 1]);
        DB::table('lieu_de_services')->insert([
            ['id' => 1, 'type' => 'DRH', 'perimetre' => 'national', 'ia_id' => null, 'ief_id' => null],
            ['id' => 2, 'type' => 'DAGE', 'perimetre' => 'national', 'ia_id' => null, 'ief_id' => null],
            ['id' => 3, 'type' => 'IA', 'perimetre' => 'regional', 'ia_id' => 1, 'ief_id' => null],
            ['id' => 4, 'type' => 'ETABLISSEMENT', 'perimetre' => 'regional', 'ia_id' => 1, 'ief_id' => 1],
        ]);
        $this->drh = $this->user('drh', 1);
        $this->dage = $this->user('dage', 2);
        $this->ia = $this->user('gestionnaire_ia', 3);
        $this->seed(RecruitmentPermissionSeeder::class);
        Sanctum::actingAs($this->drh, ['*']);
    }

    private function user(string $slug, int $structure): User
    {
        $role = Role::create(['nom' => $slug, 'slug' => $slug, 'est_actif' => true]);

        return User::create(['nom' => 'Test', 'prenom' => $slug, 'email' => $slug.'@example.test', 'password' => 'password', 'statut' => 'actif', 'role_id' => $role->id, 'lieu_service_id' => $structure]);
    }

    private function csv(string $rows = "A001;Awa;Diop;1990-01-01;vacataire;1;1;4\n"): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('recrutes.csv', implode(';', RecruitmentImporter::LEGACY_HEADERS)."\n".$rows);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('os.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
    }

    private function batch(): int
    {
        return $this->postJson('/api/recruitment/batches', ['reference' => 'LOT-1', 'recruited_at' => '2020-01-01', 'file' => $this->csv()])->assertCreated()->json('data.id');
    }

    public function test_two_years_start_at_effective_service_date_only(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-21 12:00:00'));
        $this->batch();
        DB::table('recruitment_members')->where('id', 1)->update(['engagement_since' => '2020-01-01']);
        $this->artisan('recruitment:alerts')->assertSuccessful();
        $this->assertDatabaseCount('recruitment_notices', 0);
        $this->postJson('/api/recruitment/members/1/transition', [
            'engagement' => 'contractuel', 'effective_date' => '2026-09-21', 'document' => $this->pdf(),
        ])->assertUnprocessable();

        DB::table('recruitment_members')->where('id', 1)->update(['service_date' => '2024-09-22']);
        $this->artisan('recruitment:alerts')->assertSuccessful();
        $this->assertDatabaseCount('recruitment_notices', 0);
        $this->postJson('/api/recruitment/members/1/transition', [
            'engagement' => 'contractuel', 'effective_date' => '2026-09-21', 'document' => $this->pdf(),
        ])->assertUnprocessable();

        $this->travelTo(\Carbon\Carbon::parse('2026-09-22 12:00:00'));
        $this->artisan('recruitment:alerts')->assertSuccessful();
        $this->assertDatabaseCount('recruitment_notices', 1);
        $this->assertDatabaseHas('recruitment_members', ['id' => 1, 'engagement' => 'vacataire']);
        $this->postJson('/api/recruitment/members/1/transition', [
            'engagement' => 'contractuel', 'effective_date' => '2026-09-22', 'document' => $this->pdf(),
        ])->assertOk();
        $this->travelBack();
    }

    public function test_import_is_atomic_and_inactive_and_preview_does_not_write(): void
    {
        $this->postJson('/api/recruitment/batches', ['reference' => 'preview', 'recruited_at' => '2020-01-01', 'file' => $this->csv(), 'preview' => true])->assertOk();
        $this->assertDatabaseCount('enseignants', 0);
        $this->postJson('/api/recruitment/batches', ['reference' => 'bad', 'recruited_at' => '2020-01-01', 'file' => $this->csv("A001;Awa;Diop;1990-01-01;vacataire;1;1;4\nA001;Awa;Diop;1990-01-01;vacataire;1;1;4\n")])->assertUnprocessable();
        $this->assertDatabaseCount('recruitment_batches', 0);
        $this->batch();
        $this->assertDatabaseHas('enseignants', ['matricule' => 'A001', 'est_actif' => false]);
        $this->assertDatabaseHas('recruitment_events', ['action' => 'recrutement', 'new_status' => 'vacataire']);
    }

    public function test_import_accepts_only_new_vacataires(): void
    {
        foreach (['contractuel', 'fonctionnaire'] as $type) {
            $this->postJson('/api/recruitment/batches', ['reference'=>'REF-'.$type, 'recruited_at'=>'2020-01-01',
                'file'=>$this->csv("A001;Awa;Diop;1990-01-01;$type;1;1;4\n")])->assertUnprocessable();
        }
        $this->assertDatabaseCount('enseignants', 0);
        $file = UploadedFile::fake()->createWithContent('vacataires.csv', implode(';',RecruitmentImporter::HEADERS)."\nA001;Awa;Diop;1990-01-01;1;1;4\n");
        $this->postJson('/api/recruitment/batches', ['reference'=>'VAC-1','recruited_at'=>'2020-01-01','file'=>$file])->assertCreated();
        $this->assertDatabaseHas('enseignants',['matricule'=>'A001','type_engagement'=>'vacataire','est_actif'=>false]);
    }

    public function test_search_finds_existing_teachers_and_respects_scope(): void
    {
        (require database_path('migrations/2026_09_21_000001_create_personnel_audit_logs_table.php'))->up();
        $permission = \App\Models\Admin\Permission::create(['slug'=>'enseignants.read','nom'=>'Consulter enseignants','groupe'=>'personnel','module'=>'enseignants','action'=>'read']);
        $this->drh->role->permissions()->attach($permission->id);
        $this->batch();
        DB::table('enseignants')->insert(['matricule'=>'OLD001','prenom'=>'Awa','nom'=>'Diop','ia_id'=>2]);
        $this->getJson('/api/recruitment/search?search=Diop%20Awa')->assertOk()->assertJsonPath('total',2);
        $this->getJson('/api/recruitment/search?search=OLD001')->assertOk()->assertJsonPath('data.0.batch_id',null);
        DB::table('enseignants')->where('matricule', 'A001')->update(['date_prise_service'=>'2020-02-01']);
        DB::table('enseignants')->where('matricule', 'OLD001')->update(['statut'=>'abandon', 'date_prise_service'=>'2020-02-01']);
        $this->getJson('/api/recruitment/search?situation=total_agents')->assertOk()->assertJsonPath('total',2);
        $this->getJson('/api/recruitment/search?situation=prise_service_enregistree')->assertOk()->assertJsonPath('total',1)->assertJsonPath('data.0.matricule','A001');
        $this->getJson('/api/recruitment/search?situation=enseignants_abandon')->assertOk()->assertJsonPath('total',1)->assertJsonPath('data.0.matricule','OLD001');
        $this->getJson('/api/recruitment/search?situation=incorrect')->assertUnprocessable();
        $this->drh->ia_id = 1;
        $this->getJson('/api/recruitment/search?situation=enseignants_abandon')->assertOk()->assertJsonPath('total',0);
        $this->getJson('/api/recruitment/search?search=Awa')->assertOk()->assertJsonPath('total',1)->assertJsonPath('data.0.batch_id',1);
        $this->getJson('/api/recruitment/search?search=OLD001')->assertOk()->assertJsonPath('total',0);
        $this->getJson('/api/recruitment/search?search=Absent')->assertOk()->assertJsonPath('total',0);
        $this->getJson('/api/recruitment/search?search=Awa&page=0')->assertUnprocessable();
    }

    public function test_batches_can_be_filtered_by_recruitment_year_and_reference_within_scope(): void
    {
        $this->batch();
        $this->postJson('/api/recruitment/batches', [
            'reference' => 'RECRUT-2025', 'recruited_at' => '2025-09-01',
            'file' => $this->csv("A002;Moussa;Fall;1990-01-01;vacataire;1;1;4\n"),
        ])->assertCreated();
        $this->getJson('/api/recruitment/batches?year=2025')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.reference', 'RECRUT-2025');
        $this->getJson('/api/recruitment/batches?year=2025&reference=LOT')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/recruitment/batches?reference=RECRUT')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/recruitment/batches?year=incorrect')->assertUnprocessable();
        $this->drh->ia_id = 999;
        $this->getJson('/api/recruitment/batches?year=2025')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_existing_csv_can_use_french_headers_in_any_order(): void
    {
        $file = UploadedFile::fake()->createWithContent('liste.csv', "Nom;Date de naissance;Prénoms\nDiop;1990-01-01;Awa\n");
        $this->postJson('/api/recruitment/batches', ['reference'=>'EXISTANT','recruited_at'=>'2020-01-01','file'=>$file])
            ->assertCreated();
        $this->assertDatabaseHas('enseignants',['nom'=>'Diop','prenom'=>'Awa','type_engagement'=>'vacataire','est_actif'=>false]);
    }

    public function test_complete_transmission_service_and_status_history(): void
    {
        $id = $this->batch();
        $this->postJson("/api/recruitment/batches/$id/transmit")->assertUnprocessable();
        Sanctum::actingAs($this->dage, ['*']);
        $this->getJson("/api/recruitment/batches/$id")->assertNotFound();
        Sanctum::actingAs($this->drh, ['*']);
        $this->postJson("/api/recruitment/batches/$id/os", ['document' => $this->pdf()])->assertOk();
        $this->postJson("/api/recruitment/batches/$id/transmit")->assertOk();
        $this->postJson("/api/recruitment/batches/$id/transmit")->assertConflict();
        $this->assertDatabaseCount('recruitment_notices', 1);
        Sanctum::actingAs($this->dage, ['*']);
        $this->getJson("/api/recruitment/batches/$id")->assertOk()->assertJsonPath('data.members.0.est_actif', 0);
        $this->postJson('/api/recruitment/members/1/service', ['service_date' => '2020-02-01', 'lieu_service_id' => 4, 'document' => $this->pdf()])->assertForbidden();
        Sanctum::actingAs($this->ia, ['*']);
        $this->postJson('/api/recruitment/members/1/service', ['service_date' => '2020-02-01', 'lieu_service_id' => 4, 'document' => $this->pdf()])->assertOk();
        $this->postJson('/api/recruitment/members/1/service', ['service_date' => '2020-02-01', 'lieu_service_id' => 4, 'document' => $this->pdf()])->assertConflict();
        $this->assertDatabaseHas('enseignants', ['id' => 1, 'est_actif' => true, 'date_prise_service' => '2020-02-01']);
        $this->artisan('recruitment:alerts')->assertSuccessful();
        $this->artisan('recruitment:alerts')->assertSuccessful();
        $this->assertDatabaseCount('recruitment_notices', 2);
        $this->assertDatabaseHas('recruitment_members', ['engagement' => 'vacataire']);
        Sanctum::actingAs($this->drh, ['*']);
        $this->postJson('/api/recruitment/members/1/transition', ['engagement' => 'contractuel', 'effective_date' => '2021-02-01', 'document' => $this->pdf()])->assertUnprocessable();
        $this->postJson('/api/recruitment/members/1/transition', ['engagement' => 'contractuel', 'effective_date' => '2022-02-01', 'document' => $this->pdf()])->assertOk();
        $this->assertDatabaseHas('recruitment_events', ['previous_status' => 'vacataire', 'new_status' => 'contractuel', 'effective_date' => '2022-02-01']);
        $this->assertDatabaseHas('enseignants', ['id' => 1, 'est_actif' => true, 'type_engagement' => 'contractuel']);
    }

    public function test_foreign_scope_and_private_documents_are_denied(): void
    {
        $id = $this->batch();
        $this->postJson("/api/recruitment/batches/$id/os", ['document' => $this->pdf()])->assertOk();
        $event = DB::table('recruitment_events')->where('action', 'ordre_service')->value('id');
        $this->getJson('/api/recruitment/documents/'.$event)->assertOk();
        $this->ia->ia_id = 999;
        Sanctum::actingAs($this->ia, ['*']);
        $this->getJson("/api/recruitment/batches/$id")->assertNotFound();
        $this->getJson('/api/recruitment/documents/'.$event)->assertNotFound();
        $this->postJson('/api/recruitment/batches', ['reference' => 'bad', 'recruited_at' => '2020-01-01', 'file' => $this->csv()])->assertForbidden();
    }

    public function test_missing_recipient_does_not_transmit_and_audit_failure_rolls_back_import(): void
    {
        $id = $this->batch();
        $this->postJson("/api/recruitment/batches/$id/os", ['document' => $this->pdf()])->assertOk();
        $this->dage->update(['lieu_service_id' => null]);
        $this->postJson("/api/recruitment/batches/$id/transmit")->assertUnprocessable();
        $this->assertDatabaseHas('recruitment_batches', ['id' => $id, 'transmitted_at' => null]);
        $this->assertDatabaseCount('recruitment_notices', 0);
        Schema::drop('recruitment_events');
        $this->postJson('/api/recruitment/batches', ['reference' => 'LOT-2', 'recruited_at' => '2020-01-01',
            'file' => $this->csv("A002;Moussa;Fall;1990-01-01;vacataire;1;1;4\n")])->assertStatus(500);
        $this->assertDatabaseCount('enseignants', 1);
        $this->assertDatabaseCount('recruitment_batches', 1);
    }

    public function test_ia_establishment_selector_and_notification_ownership(): void
    {
        $id = $this->batch();
        $this->postJson("/api/recruitment/batches/$id/os", ['document' => $this->pdf()])->assertOk();
        $this->postJson("/api/recruitment/batches/$id/transmit")->assertOk();
        Sanctum::actingAs($this->ia, ['*']);
        $this->getJson('/api/recruitment/establishments')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 4);
        $this->postJson('/api/recruitment/notices/1/read')->assertNotFound();
        $this->postJson('/api/recruitment/members/1/service', ['service_date' => '2020-02-01', 'lieu_service_id' => 2, 'document' => $this->pdf()])->assertNotFound();
        $this->assertDatabaseHas('enseignants', ['id' => 1, 'est_actif' => false]);
        Sanctum::actingAs($this->dage,['*']);
        $this->postJson('/api/recruitment/notices/1/read')->assertOk();
        $this->assertNotNull(DB::table('recruitment_notices')->value('read_at'));
    }
}
