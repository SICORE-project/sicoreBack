<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Édition IA : le gestionnaire d'une IA met à jour les fiches de ses
 * enseignants (effet immédiat), puis valide l'Édition de son IA pour le mois.
 * La validation recalcule les bulletins de l'IA ; l'État des salaires de l'IA
 * n'est disponible qu'après elle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_edition_validations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->restrictOnDelete();
            $table->foreignId('ia_id')->constrained('ias')->restrictOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at');
            $table->unsignedInteger('payslip_count')->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['payroll_period_id', 'ia_id']);
        });

        // Une ligne par enregistrement d'une fiche depuis l'Édition : valeurs avant / après.
        Schema::create('payroll_edition_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->restrictOnDelete();
            $table->foreignId('ia_id')->constrained('ias')->restrictOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->json('changes');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validation_id')->nullable()->constrained('payroll_edition_validations')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->index(['payroll_period_id', 'ia_id', 'validated_at'], 'payroll_edition_changes_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_edition_changes');
        Schema::dropIfExists('payroll_edition_validations');
    }
};
