<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_tabaski_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enseignant_id')->constrained('enseignants')->restrictOnDelete();
            $t->string('batch_reference', 60)->index();
            $t->unsignedBigInteger('statut_enseignant_id');
            $t->unsignedBigInteger('ia_id');
            $t->unsignedBigInteger('ief_id')->nullable();
            $t->date('advance_from_month');
            $t->decimal('advance_amount', 14, 2)->default(100000);
            $t->decimal('monthly_amount', 14, 2)->default(10000);
            $t->foreignId('advance_period_id')->nullable()->constrained('payroll_periods')->restrictOnDelete();
            $t->date('repayment_from_month')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('activated_at')->nullable();
            $t->timestamps();
            $t->unique(['enseignant_id', 'advance_from_month'], 'tabaski_teacher_campaign_unique');
        });
        Schema::create('payroll_tabaski_installments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('allocation_id')->constrained('payroll_tabaski_allocations')->restrictOnDelete();
            $t->foreignId('payroll_period_id')->constrained('payroll_periods')->restrictOnDelete();
            $t->unsignedTinyInteger('sequence');
            $t->decimal('amount', 14, 2)->default(10000);
            $t->timestamps();
            $t->unique(['allocation_id', 'sequence'], 'tabaski_installment_sequence_unique');
            $t->unique(['allocation_id', 'payroll_period_id'], 'tabaski_installment_month_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payroll_tabaski_installments');
        Schema::dropIfExists('payroll_tabaski_allocations');
    }
};
