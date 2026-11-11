<?php
namespace Tests\Unit;

use App\Models\Admin\User;
use App\Models\Personnel\Enseignant;
use App\Models\Personnel\TeacherSubmission;
use App\Services\Administration\IaScope;
use App\Services\Administration\Personnel\EnseignantService;
use App\Services\Administration\Personnel\TeacherSubmissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherSubmissionWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('ias', fn (Blueprint $table) => $table->id());
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            foreach (['cni', 'matricule', 'email', 'indice'] as $field) $table->string($field)->nullable();
        });
        Schema::create('comptes_bancaires_enseignants', function (Blueprint $table) { $table->id(); $table->string('rib'); });
        DB::table('users')->insert([['id' => 1], ['id' => 2]]);
        DB::table('ias')->insert(['id' => 1]);
        (require database_path('migrations/2026_11_08_120000_create_teacher_submissions_table.php'))->up();
        $scope = $this->createMock(IaScope::class);
        $scope->method('id')->willReturn(1);
        $this->app->instance(IaScope::class, $scope);
    }

    private function user(bool $admin = false): User
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['hasRole'])->getMock();
        $user->id = $admin ? 2 : 1;
        $user->method('hasRole')->willReturnCallback(fn ($role) => $admin && $role === 'super_admin');
        return $user;
    }

    private function data(): array
    {
        return ['ia_id' => 1, 'cni' => '1234567890123', 'matricule' => '123456789/A',
            'compte_bancaire' => ['rib' => 'SN0120130103515989670197']];
    }

    public function test_submission_is_not_a_teacher_until_dage_approval_and_approval_is_idempotent(): void
    {
        $service = app(TeacherSubmissionService::class);
        $submission = $service->submit($this->user(), $this->data());
        self::assertSame('pending', $submission->status);
        $this->assertDatabaseCount('enseignants', 0);
        $creator = $this->createMock(EnseignantService::class);
        $creator->expects(self::once())->method('create')->willReturnCallback(function ($data, $id) {
            self::assertSame(1, $id);
            DB::table('enseignants')->insert(['id' => 8, 'cni' => $data['cni']]);
            return (new Enseignant())->forceFill(['id' => 8]);
        });
        $this->app->instance(EnseignantService::class, $creator);
        $approved = $service->approve($this->user(true), $submission->id);
        self::assertSame('approved', $approved->status);
        self::assertSame(2, (int) $approved->approved_by);
        self::assertSame(8, (int) $approved->enseignant_id);
        self::assertNotNull($approved->approved_at);
        $service->approve($this->user(true), $submission->id);
        $this->assertDatabaseCount('enseignants', 1);
    }

    public function test_ia_cannot_submit_for_another_ia(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(TeacherSubmissionService::class)->submit($this->user(), array_replace($this->data(), ['ia_id' => 2]));
    }

    public function test_ia_cannot_approve_its_submission(): void
    {
        $service = app(TeacherSubmissionService::class);
        $submission = $service->submit($this->user(), $this->data());
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->approve($this->user(), $submission->id);
    }

    public function test_duplicate_submission_is_rejected(): void
    {
        $service = app(TeacherSubmissionService::class);
        $service->submit($this->user(), $this->data());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->submit($this->user(), $this->data());
    }

    public function test_approval_conflict_keeps_submission_pending(): void
    {
        $service = app(TeacherSubmissionService::class);
        $submission = $service->submit($this->user(), $this->data());
        DB::table('enseignants')->insert(['id' => 1, 'cni' => $this->data()['cni']]);
        try {
            $service->approve($this->user(true), $submission->id);
            self::fail('Duplicate should prevent approval');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            self::assertSame('pending', $submission->fresh()->status);
            self::assertNull($submission->fresh()->enseignant_id);
        }
    }
}
