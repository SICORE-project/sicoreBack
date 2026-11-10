<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Services\Administration\OrganizationalScope;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\BuildsTeacherSchema;
use Tests\TestCase;

class TeacherCsvImportTest extends TestCase
{
    use BuildsTeacherSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildTeacherSchema();
        $this->withoutMiddleware();
        \Illuminate\Support\Facades\Schema::table('comptes_bancaires_enseignants', function ($table) {
            $table->string('rib')->nullable()->unique();
        });
        $user = $this->getMockBuilder(User::class)->onlyMethods(['hasRole'])->getMock();
        $user->id = 8;
        $user->method('hasRole')->willReturn(false);
        $this->actingAs($user);
    }

    private function upload(array $rows, array $extra = [])
    {
        $headers = ['matricule', 'nom', 'prenom', 'date_naissance', 'cni', 'telephone',
            'statut_enseignant_id', 'ia_id', 'ief_id', 'situation_matrimoniale', 'nombre_enfants',
            'code_banque', 'code_guichet', 'numero_compte', 'cle_rib'];
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, array_merge($headers, array_keys($extra)), ';', '"', '');
        foreach ($rows as $changes) {
            $row = array_replace(array_combine($headers, ['202600001/A', 'Diop', 'Awa', '1990-01-01',
                '1234567890123', '771234567', '3', '1', '2', 'celibataire', '0',
                'SN100', '00001', '000123456789', '01']), $extra, $changes);
            fputcsv($stream, array_values($row), ';', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return $this->postJson('/api/admin/personnel/enseignants/import', [
            'csv' => UploadedFile::fake()->createWithContent('enseignants.csv', $csv),
        ]);
    }

    public function test_import_uses_creation_rules_and_preserves_bank_zeroes(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        $this->upload([[]])->assertCreated()->assertJsonPath('count', 1);
        $this->assertDatabaseHas('enseignants', ['matricule' => '202600001/A', 'created_by' => 8]);
        $this->assertDatabaseHas('comptes_bancaires_enseignants', ['numero_compte' => '000123456789']);
        $this->assertDatabaseCount('payroll_audit_logs', 1);
    }

    public function test_reduced_template_derives_ia_and_rib_without_weakening_scope(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')
            ->withArgs(fn ($user, $teacher) => (int) $teacher->ia_id === 1 && (int) $teacher->ief_id === 2)
            ->andReturn(true);
        $csv = "matricule;nom;prenom;date_naissance;cni;telephone;statut_enseignant_id;ief_id;situation_matrimoniale;nombre_enfants;code_banque;code_guichet;numero_compte\n"
            ."202600001/A;Diop;Awa;1990-01-01;1234567890123;771234567;3;2;celibataire;0;SN100;00001;000123456789\n";
        $this->postJson('/api/admin/personnel/enseignants/import', [
            'csv' => UploadedFile::fake()->createWithContent('enseignants.csv', $csv),
        ])->assertCreated()->assertJsonPath('count', 1);
        $this->assertDatabaseHas('enseignants', ['ia_id' => 1, 'ief_id' => 2, 'statut' => 'en_activite']);
        $this->assertDatabaseHas('comptes_bancaires_enseignants', [
            'rib' => 'SN1000000100012345678927', 'cle_rib' => '27',
        ]);
    }

    private function uploadLabels(string $ief = 'Dakar Plateau', string $separator = ';', string $encoding = 'UTF-8')
    {
        $csv = "matricule;nom;prenom;date_naissance;cni;telephone;statut_enseignant;ief;situation_matrimoniale;nombre_enfants;code_banque;code_guichet;numero_compte\n"
            ."202600001/A;Diop;Awa;1990-01-01;1234567890123;771234567;vacataire;{$ief};celibataire;0;SN100;00001;000123456789\n";
        $csv = str_replace(';', $separator, $csv);
        $csv = mb_convert_encoding($csv, $encoding, 'UTF-8');
        return $this->postJson('/api/admin/personnel/enseignants/import', [
            'csv' => UploadedFile::fake()->createWithContent('enseignants.csv', $csv),
        ]);
    }

    public function test_labels_resolve_references_without_ids(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        $this->uploadLabels('DAKAR plateau')->assertCreated()->assertJsonPath('count', 1);
        $this->assertDatabaseHas('enseignants', ['statut_enseignant_id' => 3, 'ief_id' => 2, 'ia_id' => 1]);
    }

    public function test_tab_separated_file_with_csv_extension_is_imported(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        $this->uploadLabels('Dakar Plateau', "\t")->assertCreated()->assertJsonPath('count', 1);
        $this->assertDatabaseCount('enseignants', 1);
    }

    public function test_excel_windows_encoding_returns_readable_accented_line_error(): void
    {
        $this->uploadLabels('École inconnue', "\t", 'Windows-1252')->assertOk()
            ->assertJsonPath('blocked.0.errors.ief.0', 'Le libellé « École inconnue » est introuvable dans le référentiel ou le périmètre autorisé.');
    }

    public function test_excel_windows_encoding_preserves_accents_when_importing(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        \Illuminate\Support\Facades\DB::table('iefs')->where('id', 2)->update(['libelle' => 'École Plateau']);
        $this->uploadLabels('École Plateau', "\t", 'Windows-1252')->assertCreated()->assertJsonPath('count', 1);
    }

    public function test_comma_separated_file_is_still_imported(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        $this->uploadLabels('Dakar Plateau', ',')->assertCreated()->assertJsonPath('count', 1);
    }

    public function test_missing_header_error_names_the_missing_columns(): void
    {
        $this->postJson('/api/admin/personnel/enseignants/import', [
            'csv' => UploadedFile::fake()->createWithContent('enseignants.csv', "matricule;nom\n"),
        ])->assertUnprocessable()->assertJsonPath('errors.csv.0', fn ($message) =>
            str_contains($message, 'Colonnes obligatoires manquantes :') && str_contains($message, 'statut_enseignant'));
    }

    public function test_unknown_label_is_reported_on_its_row(): void
    {
        $this->uploadLabels('IEF inconnue')->assertOk()->assertJsonPath('count', 0)
            ->assertJsonPath('blocked.0.line', 2)->assertJsonStructure(['blocked' => [['errors' => ['ief']]]]);
        $this->assertDatabaseCount('enseignants', 0);
    }

    public function test_category_diploma_and_establishment_labels_resolve_in_their_hierarchy(): void
    {
        \Illuminate\Support\Facades\DB::table('corps_enseignant')->where('id', 4)->update(['code' => 'PC']);
        \Illuminate\Support\Facades\DB::table('lieu_de_services')->insert([
            'id' => 12, 'libelle' => 'École Plateau', 'ief_id' => 2, 'ia_id' => 1,
        ]);
        $resolved = app(\App\Services\Administration\Personnel\TeacherImportReferences::class)->resolve([
            'statut_enseignant' => 'Contractuel', 'corps' => 'Contractuel', 'categorie' => 'a1',
            'diplome' => 'LICENCE', 'ief' => 'Dakar Plateau', 'lieu_service' => 'ecole plateau',
        ], auth()->user());
        $this->assertSame(4, $resolved['corps_id']);
        $this->assertSame(5, $resolved['categorie_id']);
        $this->assertSame(6, $resolved['diplome_id']);
        $this->assertSame(12, $resolved['lieu_service_id']);
        $this->assertSame(1, $resolved['ia_id']);
    }

    public function test_ambiguous_label_is_blocked_instead_of_choosing_first_match(): void
    {
        \Illuminate\Support\Facades\DB::table('iefs')->insert(['id' => 9, 'ia_id' => 99, 'libelle' => 'Dakar Plateau']);
        $this->uploadLabels()->assertOk()->assertJsonPath('blocked.0.errors.ief.0',
            'Le libellé « Dakar Plateau » est ambigu : plusieurs correspondances.');
        $this->assertDatabaseCount('enseignants', 0);
    }

    public function test_ia_label_is_resolved_within_manager_scope_and_keeps_dage_workflow(): void
    {
        $this->actAsIaManager();
        \Illuminate\Support\Facades\DB::table('iefs')->insert(['id' => 9, 'ia_id' => 99, 'libelle' => 'Dakar Plateau']);
        $this->uploadLabels()->assertCreated()->assertJsonPath('status', 'pending');
        $this->assertDatabaseHas('teacher_submissions', ['ia_id' => 1, 'status' => 'pending']);
        $this->assertDatabaseCount('enseignants', 0);
    }

    public function test_duplicate_is_blocked_without_rolling_back_valid_row(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        $this->upload([[], []])->assertCreated()->assertJsonPath('count', 1)
            ->assertJsonPath('accepted.0.line', 2)->assertJsonPath('blocked.0.line', 3)
            ->assertJsonStructure(['blocked' => [['errors' => ['matricule', 'cni', 'compte_bancaire.rib']]]]);
        $this->assertDatabaseCount('enseignants', 1);
        $this->assertDatabaseCount('comptes_bancaires_enseignants', 1);
        $this->assertDatabaseCount('payroll_audit_logs', 1);
    }

    public function test_import_rejects_teachers_outside_scope(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(false);
        $this->upload([[]])->assertOk()->assertJsonPath('count', 0)->assertJsonPath('blocked.0.line', 2);
        $this->assertDatabaseCount('enseignants', 0);
    }

    public function test_import_checks_ief_belongs_to_ia(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        \Illuminate\Support\Facades\DB::table('iefs')->where('id', 2)->update(['ia_id' => 99]);
        $this->upload([[]])->assertOk()->assertJsonPath('count', 0)->assertJsonPath('blocked.0.line', 2);
        $this->assertDatabaseCount('enseignants', 0);
    }

    public function test_import_rejects_unknown_and_nested_columns(): void
    {
        $this->upload([[]], ['compte_bancaire' => 'invalid'])->assertUnprocessable()
            ->assertJsonValidationErrors('csv');
        $this->assertDatabaseCount('enseignants', 0);
    }
    private function actAsIaManager(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        $this->mock(\App\Services\Administration\IaScope::class)->shouldReceive('id')->andReturn(1);
        (require database_path('migrations/2026_11_08_120000_create_teacher_submissions_table.php'))->up();
        $user = $this->getMockBuilder(User::class)->onlyMethods(['hasRole'])->getMock();
        $user->id = 8;
        $user->method('hasRole')->willReturnCallback(fn ($role) => $role === 'gestionnaire_ia');
        $this->actingAs($user);
    }

    public function test_ia_import_creates_only_pending_dossiers_until_dage_approval(): void
    {
        $this->actAsIaManager();
        $this->upload([[]])->assertCreated()->assertJsonPath('status', 'pending');
        $this->assertDatabaseCount('enseignants', 0);
        $this->assertDatabaseCount('comptes_bancaires_enseignants', 0);
        $this->assertDatabaseHas('teacher_submissions', ['status' => 'pending', 'submitted_by' => 8]);
        $reviewer = $this->getMockBuilder(User::class)->onlyMethods(['hasRole'])->getMock();
        $reviewer->id = 8;
        $reviewer->method('hasRole')->willReturnCallback(fn ($role) => $role === 'super_admin');
        $submission = \App\Models\Personnel\TeacherSubmission::sole();
        app(\App\Services\Administration\Personnel\TeacherSubmissionService::class)->approve($reviewer, $submission->id);
        $this->assertDatabaseCount('enseignants', 1);
        $this->assertDatabaseHas('teacher_submissions', ['id' => $submission->id, 'status' => 'approved']);
    }

    public function test_duplicate_ia_dossier_is_blocked_and_valid_submission_is_kept(): void
    {
        $this->actAsIaManager();
        $this->upload([[], []])->assertCreated()->assertJsonPath('count', 1)
            ->assertJsonPath('blocked_count', 1)->assertJsonPath('blocked.0.line', 3);
        $this->assertDatabaseCount('teacher_submissions', 1);
        $this->assertDatabaseCount('enseignants', 0);
    }
    public function test_invalid_first_row_does_not_prevent_a_later_valid_row(): void
    {
        $this->mock(OrganizationalScope::class)->shouldReceive('allows')->andReturn(true);
        $this->upload([['cni' => 'bad', 'telephone' => 'invalid'], []])->assertCreated()
            ->assertJsonPath('count', 1)->assertJsonPath('accepted.0.line', 3)
            ->assertJsonPath('blocked.0.line', 2)
            ->assertJsonStructure(['blocked' => [['errors' => ['cni', 'telephone']]]]);
        $this->assertDatabaseCount('enseignants', 1);
    }
}
