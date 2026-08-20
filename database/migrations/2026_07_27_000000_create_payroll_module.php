<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->string('matricule')->nullable()->unique()->after('id');
            $table->decimal('salaire_base', 14, 2)->default(0)->after('indice');
            $table->unsignedTinyInteger('nombre_parts')->default(1)->after('salaire_base');
            $table->boolean('actif')->default(true)->after('nombre_parts');
            $table->string('numero_compte')->nullable()->after('actif');
            $table->foreignId('etablissement_id')
                ->nullable()
                ->after('institution_financiere_id')
                ->constrained('etablissements')
                ->nullOnDelete();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 7)->unique();
            $table->string('label');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'calculated', 'validated', 'closed'])->default('open');
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('employee_count')->default(0);
            $table->decimal('total_gross', 16, 2)->default(0);
            $table->decimal('total_deductions', 16, 2)->default(0);
            $table->decimal('total_net', 16, 2)->default(0);
            $table->char('checksum', 64)->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'start_date']);
        });

        Schema::create('payroll_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->restrictOnDelete();
            $table->decimal('absence_days', 5, 2)->default(0);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->decimal('deduction_amount', 14, 2)->default(0);
            $table->enum('status', ['draft', 'validated'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['payroll_period_id', 'enseignant_id'], 'payroll_attendance_unique');
            $table->index(['payroll_period_id', 'status']);
        });

        Schema::create('payroll_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('label');
            $table->enum('category', ['earning', 'deduction', 'contribution']);
            $table->enum('source', ['manual', 'attendance', 'system', 'import'])->default('manual');
            $table->decimal('amount', 14, 2);
            $table->boolean('is_exempt')->default(false);
            $table->text('exemption_reason')->nullable();
            $table->enum('status', ['draft', 'validated'])->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['payroll_period_id', 'enseignant_id', 'code'],
                'payroll_element_unique'
            );
            $table->index(['payroll_period_id', 'category', 'status']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->enum('status', ['calculated', 'validated', 'closed'])->default('calculated');
            $table->unsignedInteger('employee_count')->default(0);
            $table->decimal('total_gross', 16, 2)->default(0);
            $table->decimal('total_deductions', 16, 2)->default(0);
            $table->decimal('total_employer_contributions', 16, 2)->default(0);
            $table->decimal('total_net', 16, 2)->default(0);
            $table->char('checksum', 64);
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->restrictOnDelete();
            $table->string('reference')->unique();
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('deduction_amount', 14, 2);
            $table->decimal('employer_contribution_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->enum('payment_status', ['pending', 'paid', 'rejected'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['payroll_period_id', 'enseignant_id'], 'payroll_payslip_unique');
            $table->index(['payroll_period_id', 'payment_status']);
        });

        Schema::create('payroll_payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_payslip_id')->constrained('payroll_payslips')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('label');
            $table->enum('category', ['earning', 'deduction', 'contribution', 'employer_contribution']);
            $table->decimal('amount', 14, 2);
            $table->string('source', 30)->default('system');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['payroll_payslip_id', 'code'], 'payroll_payslip_line_unique');
        });

        Schema::create('payroll_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('auditable_type', 80);
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_audit_logs');
        Schema::dropIfExists('payroll_payslip_lines');
        Schema::dropIfExists('payroll_payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_elements');
        Schema::dropIfExists('payroll_attendances');
        Schema::dropIfExists('payroll_periods');

        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etablissement_id');
            $table->dropUnique(['matricule']);
            $table->dropColumn([
                'matricule',
                'salaire_base',
                'nombre_parts',
                'actif',
                'numero_compte',
            ]);
        });
    }
};
