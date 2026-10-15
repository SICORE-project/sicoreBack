<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Models\Admin\Role;
use App\Models\Personnel\Enseignant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherPersonalAccessTest extends TestCase
{
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('enseignants', function (Blueprint $t) {
            $t->id(); $t->string('matricule'); $t->string('prenom'); $t->string('nom'); $t->softDeletes();
        });
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->id(); $t->string('label'); $t->date('start_date'); $t->string('status');
        });
        Schema::create('payroll_payslips', function (Blueprint $t) {
            $t->id(); $t->integer('enseignant_id'); $t->integer('payroll_period_id'); $t->string('reference');
            $t->decimal('gross_amount')->default(100); $t->decimal('deduction_amount')->default(10); $t->decimal('net_amount')->default(90); $t->timestamps();
        });
        Schema::create('payroll_payslip_lines', function (Blueprint $t) {
            $t->id(); $t->integer('payroll_payslip_id'); $t->integer('sort_order'); $t->string('label'); $t->string('category'); $t->decimal('amount');
        });
        Schema::create('payroll_audit_logs', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('action'); $t->string('auditable_type'); $t->integer('auditable_id'); $t->timestamp('created_at')->nullable();
        });
        DB::table('enseignants')->insert(['id'=>1,'matricule'=>'OWN','prenom'=>'Test','nom'=>'Enseignant']);
        DB::table('payroll_periods')->insert([
            ['id'=>1,'label'=>'Janvier 2026','start_date'=>'2026-01-01','status'=>'validated'],
            ['id'=>2,'label'=>'Janvier 2025','start_date'=>'2025-01-01','status'=>'closed'],
            ['id'=>3,'label'=>'Brouillon','start_date'=>'2026-02-01','status'=>'calculated'],
        ]);
        foreach ([[1,1,1],[2,2,1],[3,1,2],[4,1,3]] as [$id,$teacher,$period]) {
            DB::table('payroll_payslips')->insert(['id'=>$id,'enseignant_id'=>$teacher,'payroll_period_id'=>$period,'reference'=>'B'.$id,'created_at'=>now(),'updated_at'=>now()]);
        }
        $this->teacher = new User(['statut'=>'actif','enseignant_id'=>1]);
        $this->teacher->id=1;
        $this->teacher->setRelation('role', new Role(['slug'=>'enseignant']));
        Sanctum::actingAs($this->teacher);
    }

    public function test_only_own_published_payslips_and_filters_are_available(): void
    {
        $this->getJson('/api/enseignant/dossier?enseignant_id=2')->assertOk()->assertJsonPath('data.matricule','OWN');
        $this->getJson('/api/enseignant/bulletins?enseignant_id=2')->assertOk()->assertJsonPath('data.total',2);
        $this->getJson('/api/enseignant/bulletins?annee=2025')->assertOk()->assertJsonPath('data.data.0.id',3)->assertJsonPath('data.total',1);
        $this->getJson('/api/enseignant/bulletins?periode_id=3')->assertOk()->assertJsonPath('data.total',0);
    }

    public function test_other_teacher_and_unpublished_pdf_are_refused(): void
    {
        $this->getJson('/api/enseignant/bulletins/2/pdf')->assertNotFound();
        $this->getJson('/api/enseignant/bulletins/4/pdf')->assertNotFound();
        $this->assertSame(0,DB::table('payroll_audit_logs')->count());
    }

    public function test_personal_pdf_and_download_are_private_and_audited(): void
    {
        $this->get('/api/enseignant/bulletins/1/pdf')->assertOk()->assertHeader('Content-Type','application/pdf');
        $response=$this->get('/api/enseignant/bulletins/1/pdf?download=1')->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(2,DB::table('payroll_audit_logs')->count());
    }

    public function test_management_routes_and_accounts_without_a_dossier_are_closed(): void
    {
        foreach (['/api/admin/users/all','/api/enseignants','/api/ia/enseignants','/api/lieux-service'] as $path) $this->getJson($path)->assertForbidden();
        $this->teacher->enseignant_id=null;
        $this->teacher->unsetRelation('enseignant');
        $this->getJson('/api/enseignant/bulletins')->assertForbidden();
    }

    public function test_inactive_teacher_cannot_read_payslips(): void
    {
        $this->teacher->statut='inactif';
        $this->getJson('/api/enseignant/bulletins')->assertForbidden();
    }
}
