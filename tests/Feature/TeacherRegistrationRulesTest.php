<?php
namespace Tests\Feature;

use App\Http\Requests\Administration\Personnel\StoreEnseignantRequest;
use App\Models\Admin\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsTeacherSchema;
use Tests\TestCase;

class TeacherRegistrationRulesTest extends TestCase
{
    use BuildsTeacherSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildTeacherSchema();
        $this->withoutMiddleware();
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'matricule' => '202600001/A', 'nom' => 'Diop', 'prenom' => 'Awa',
            'date_naissance' => '1990-01-01', 'cni' => '1234567890123', 'telephone' => '771234567',
            'statut_enseignant_id' => 2, 'ia_id' => 1, 'ief_id' => 2,
            'situation_matrimoniale' => 'celibataire', 'nombre_enfants' => 3, 'est_actif' => true,
            'compte_bancaire' => ['code_banque' => '00100', 'code_guichet' => '00001', 'numero_compte' => '000123456789', 'cle_rib' => '01'],
        ], $changes);
    }

    public function test_single_parent_keeps_children_and_has_same_part_calculation_as_married(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload())
            ->assertCreated()->assertJsonPath('data.nombre_enfants', 3)
            ->assertJsonPath('data.nombre_parts_fiscales', 3.5)->assertJsonPath('data.nombre_femmes', 0);
    }

    public function test_all_required_identity_and_bank_fields_are_validated(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload([
            'cni' => null, 'telephone' => null, 'nombre_enfants' => null, 'compte_bancaire' => [],
        ]))->assertUnprocessable()->assertJsonValidationErrors([
            'cni', 'telephone', 'nombre_enfants', 'compte_bancaire.code_banque',
            'compte_bancaire.code_guichet', 'compte_bancaire.numero_compte', 'compte_bancaire.cle_rib',
        ]);
    }

    public function test_official_requires_a_corps_but_has_no_family_section(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload([
            'statut_enseignant_id' => 1, 'matricule' => '001234/F', 'indice' => '0012',
        ]))->assertUnprocessable()->assertJsonValidationErrors('corps_id');
        $this->postJson('/api/admin/personnel/enseignants', $this->payload([
            'statut_enseignant_id' => 1, 'corps_id' => 5, 'matricule' => '001234/F', 'indice' => '0012',
            'statut' => 'abandon',
        ]))->assertCreated()->assertJsonPath('data.nombre_enfants', 0)->assertJsonPath('data.statut', 'abandon');
    }

    public function test_non_official_ignores_disabled_career_fields_and_persists_widowed_state(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload([
            'corps_id' => 5, 'salaire_brut' => 999999, 'diplome_id' => 6, 'observations' => 'ignored',
            'situation_matrimoniale' => 'veuf',
        ]))->assertCreated()->assertJsonPath('data.corps', null)->assertJsonPath('data.salaire_brut', null)
            ->assertJsonPath('data.situation_matrimoniale', 'veuf');
    }

    public function test_direct_model_updates_clear_corps_when_status_changes(): void
    {
        $id = $this->postJson('/api/admin/personnel/enseignants', $this->payload([
            'statut_enseignant_id' => 1, 'corps_id' => 5, 'matricule' => '001234/F', 'indice' => '0012',
        ]))->assertCreated()->json('data.id');
        $teacher = \App\Models\Personnel\Enseignant::findOrFail($id);
        $teacher->update(['statut_enseignant_id' => 3]);
        $this->assertNull($teacher->fresh()->corps_id);
        $this->getJson('/api/admin/personnel/enseignants/'.$id)->assertOk()
            ->assertJsonPath('data.corps', null)->assertJsonPath('data.statut_enseignant.libelle', 'Vacataire');
    }

    public function test_migration_clears_legacy_corps_without_creating_statuses(): void
    {
        $id = $this->postJson('/api/admin/personnel/enseignants', $this->payload())
            ->assertCreated()->json('data.id');
        DB::table('enseignants')->where('id', $id)->update(['corps_id' => 4, 'statut_enseignant_id' => null]);
        $migration = require database_path('migrations/2026_10_13_000002_enforce_teacher_corps_by_status.php');
        $migration->up();
        $this->assertDatabaseHas('enseignants', ['id' => $id, 'statut_enseignant_id' => 2, 'corps_id' => null]);
        $this->assertSame(3, DB::table('statuts_enseignant')->count());
    }

    public function test_status_catalogue_supports_crud_and_protects_used_statuses(): void
    {
        $id = $this->postJson('/api/statuts-enseignant', ['libelle' => 'Stagiaire'])
            ->assertCreated()->json('data.id');
        $this->getJson('/api/statuts-enseignant')->assertOk()->assertJsonPath('data.total', 4);
        $this->putJson('/api/statuts-enseignant/'.$id, ['libelle' => 'Stagiaire temporaire'])->assertOk();
        $this->deleteJson('/api/statuts-enseignant/'.$id)->assertOk();
        $this->postJson('/api/admin/personnel/enseignants', $this->payload())->assertCreated();
        $this->deleteJson('/api/statuts-enseignant/2')->assertStatus(409);
    }

    public function test_csv_import_rolls_back_every_row_when_a_later_row_is_invalid(): void
    {
        $this->mock(\App\Services\Administration\OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        $this->actingAs(User::find(8));
        $header = 'matricule;nom;prenom;date_naissance;cni;telephone;statut_enseignant_id;ia_id;ief_id;situation_matrimoniale;nombre_enfants;code_banque;code_guichet;numero_compte;cle_rib';
        $valid = '202600001/A;Diop;Awa;1990-01-01;1234567890123;771234567;2;1;2;celibataire;3;00100;00001;000123456789;01';
        $invalid = str_replace('202600001/A', '202600002/B', $valid);
        $invalid = str_replace('1234567890123', 'bad', $invalid);
        $this->postJson('/api/admin/personnel/enseignants/import', ['csv' => UploadedFile::fake()->createWithContent('enseignants.csv', "$header\n$valid\n$invalid\n")])
            ->assertUnprocessable()->assertJsonValidationErrors('csv');
        $this->assertDatabaseCount('enseignants', 0);
        $this->assertDatabaseCount('payroll_audit_logs', 0);
        $this->postJson('/api/admin/personnel/enseignants/import', ['csv' => UploadedFile::fake()->createWithContent('enseignants.csv', "$header\n$valid\n")])
            ->assertCreated()->assertJsonPath('count', 1);
        $this->assertDatabaseHas('comptes_bancaires_enseignants', ['numero_compte' => '000123456789']);
    }
}
