<?php

namespace Tests\Feature;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\PayrollAuditLog;
use App\Services\Administration\Personnel\EnseignantService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsTeacherSchema;
use Tests\TestCase;

class TeacherCorpsIdentityTest extends TestCase
{
    use BuildsTeacherSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildTeacherSchema();
        // Keep input-normalization middleware enabled to verify whitespace rejection.
        $this->withoutMiddleware([Authenticate::class, PermissionMiddleware::class]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'matricule' => '001234/F', 'indice' => '1500',
            'nom' => 'Ndiaye', 'prenom' => 'Awa', 'date_naissance' => '1990-01-01',
            'ia_id' => 1, 'ief_id' => 2, 'corps_id' => 5,
            'est_en_couple' => false, 'statut' => 'en_activite', 'est_actif' => true,
        ], $overrides);
    }

    public function test_civil_servant_is_created_with_six_digits_and_required_index(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload())
            ->assertCreated()->assertJsonPath('data.matricule', '001234/F')
            ->assertJsonPath('data.corps.id', 5)->assertJsonPath('data.indice', '1500');
        $this->assertDatabaseHas('enseignants', ['matricule' => '001234/F', 'indice' => 1500]);
        $audit = PayrollAuditLog::sole();
        $this->assertSame('teacher.created', $audit->action);
        $this->assertSame('001234/F', $audit->after['matricule']);
        $this->assertEquals(5, $audit->after['corps_id']);
        $this->assertEquals(1500, $audit->after['indice']);
        $this->assertNotNull($audit->created_at);
    }

    public function test_non_civil_servant_uses_nine_digits_and_ignores_any_submitted_index(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload([
            'corps_id' => 3, 'matricule' => '001234567/H', 'indice' => ['not', 'applicable'],
        ]))->assertCreated()->assertJsonPath('data.indice', null);
        $this->assertDatabaseHas('enseignants', ['matricule' => '001234567/H', 'corps_id' => 3, 'indice' => null]);
        $this->assertNull(PayrollAuditLog::sole()->after['indice']);
    }

    public static function invalidIdentity(): array
    {
        return [
            'missing corps' => [['corps_id' => null], 'corps_id'],
            'unknown corps' => [['corps_id' => 999], 'corps_id'],
            'five digits' => [['matricule' => '12345'], 'matricule'],
            'seven digits' => [['matricule' => '1234567'], 'matricule'],
            'civil servant nine digits' => [['matricule' => '123456789'], 'matricule'],
            'non civil servant six digits' => [['corps_id' => 3], 'matricule'],
            'non civil servant eight digits' => [['corps_id' => 3, 'matricule' => '12345678'], 'matricule'],
            'letters' => [['matricule' => 'A12345'], 'matricule'],
            'punctuation' => [['matricule' => '123-45'], 'matricule'],
            'internal space' => [['matricule' => '12 345'], 'matricule'],
            'leading space' => [['matricule' => ' 001234/F'], 'matricule'],
            'trailing space' => [['matricule' => '001234/F '], 'matricule'],
            'newline' => [['matricule' => "001234/F\n"], 'matricule'],
            'unicode digits' => [['matricule' => '١٢٣٤٥٦'], 'matricule'],
            'numeric JSON value' => [['matricule' => 123456], 'matricule'],
            'missing index' => [['indice' => null], 'indice'],
            'three digit index' => [['indice' => '123'], 'indice'],
            'seven digit index' => [['indice' => '1234567'], 'indice'],
            'zero index' => [['indice' => 0], 'indice'],
            'negative index' => [['indice' => -1], 'indice'],
            'decimal index' => [['indice' => '1.5'], 'indice'],
            'text index' => [['indice' => 'A'], 'indice'],
            'oversized index' => [['indice' => '2147483648'], 'indice'],
        ];
    }

    #[DataProvider('invalidIdentity')]
    public function test_invalid_identity_is_rejected_without_creation_or_audit(array $values, string $field): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload($values))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('enseignants', 0);
        $this->assertDatabaseCount('payroll_audit_logs', 0);
    }

    public function test_duplicate_matricule_is_rejected_with_a_precise_message(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload())->assertCreated();
        $this->postJson('/api/admin/personnel/enseignants', $this->payload())
            ->assertUnprocessable()->assertJsonPath('errors.matricule.0', 'Ce matricule existe déjà.');
        $this->assertDatabaseCount('enseignants', 1);
        $this->assertDatabaseCount('payroll_audit_logs', 1);
    }

    public function test_index_is_unique_on_creation_and_update_but_teacher_can_keep_own_identifiers(): void
    {
        $id = $this->postJson('/api/admin/personnel/enseignants', $this->payload())->assertCreated()->json('data.id');
        $this->postJson('/api/admin/personnel/enseignants', $this->payload(['matricule' => '001235/F']))
            ->assertUnprocessable()->assertJsonPath('errors.indice.0', 'Cet indice est déjà attribué à un autre enseignant.');
        $other = $this->postJson('/api/admin/personnel/enseignants', $this->payload(['matricule' => '001235/F', 'indice' => '2000']))
            ->assertCreated()->json('data.id');
        $this->putJson('/api/admin/personnel/enseignants/'.$id, $this->payload())->assertOk();
        $this->putJson('/api/admin/personnel/enseignants/'.$other, $this->payload(['matricule' => '001235/F']))
            ->assertUnprocessable()->assertJsonValidationErrors('indice');
        $this->putJson('/api/admin/personnel/enseignants/'.$other, $this->payload(['indice' => '2000']))
            ->assertUnprocessable()->assertJsonValidationErrors('matricule');
        $this->assertDatabaseCount('enseignants', 2);
    }

    public function test_database_prevents_duplicate_indices_even_without_request_validation(): void
    {
        $service = new EnseignantService;
        $service->create($this->payload());
        try {
            $service->create($this->payload(['matricule' => '001235/F']));
            $this->fail('Expected unique constraint violation');
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            $this->assertDatabaseCount('enseignants', 1);
            $this->assertDatabaseCount('payroll_audit_logs', 1);
        }
    }

    public function test_switching_corps_revalidates_matricule_and_clears_index(): void
    {
        $id = $this->postJson('/api/admin/personnel/enseignants', $this->payload())->json('data.id');
        $this->putJson('/api/admin/personnel/enseignants/'.$id, $this->payload([
            'corps_id' => 3,
        ]))->assertUnprocessable()->assertJsonValidationErrors('matricule');
        $this->putJson('/api/admin/personnel/enseignants/'.$id, $this->payload([
            'corps_id' => 3, 'matricule' => '001234567/H',
        ]))->assertOk()->assertJsonPath('data.indice', null);
        $this->assertDatabaseHas('enseignants', ['id' => $id, 'corps_id' => 3, 'indice' => null]);
    }

    public function test_partial_identity_update_uses_saved_corps_and_does_not_flag_own_matricule_as_duplicate(): void
    {
        $id = $this->postJson('/api/admin/personnel/enseignants', $this->payload())->json('data.id');
        $partial = ['date_naissance' => '1990-01-01', 'est_en_couple' => false];
        $this->putJson('/api/admin/personnel/enseignants/'.$id, $partial + ['indice' => '2000'])
            ->assertOk()->assertJsonPath('data.indice', '2000')->assertJsonPath('data.matricule', '001234/F');
        $this->putJson('/api/admin/personnel/enseignants/'.$id, $partial + ['matricule' => '123456789'])
            ->assertUnprocessable()->assertJsonValidationErrors('matricule');
    }

    public function test_audit_records_actor_and_failure_to_write_audit_rolls_back_teacher_creation(): void
    {
        $teacher = (new EnseignantService)->create($this->payload(), 8);
        $this->assertDatabaseHas('payroll_audit_logs', ['user_id' => 8, 'auditable_id' => $teacher->id, 'action' => 'teacher.created']);
        Schema::drop('payroll_audit_logs');
        $this->postJson('/api/admin/personnel/enseignants', $this->payload(['matricule' => '001235/F', 'indice' => '2000']))->assertStatus(500);
        $this->assertDatabaseCount('enseignants', 1);
    }

    public function test_index_accepts_four_to_six_digits_and_preserves_leading_zeroes(): void
    {
        foreach (['0012', '12345', '123456'] as $number => $indice) {
            $this->postJson('/api/admin/personnel/enseignants', $this->payload([
                'matricule' => '54367'.$number.'/F', 'indice' => $indice,
            ]))->assertCreated()->assertJsonPath('data.indice', $indice);
        }
    }

    public function test_client_supplied_administrative_status_cannot_override_corps(): void
    {
        $this->postJson('/api/admin/personnel/enseignants', $this->payload([
            'statut_administratif' => 'non_fonctionnaire', 'indice' => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors('indice');
    }
}
