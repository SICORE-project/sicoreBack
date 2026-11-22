<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les fiches sont modifiées depuis Gestion du personnel > Enseignants, hors
 * de tout mois : une modification remet « à revalider » chaque mois non
 * clôturé de l'IA validé avant elle (comparaison des dates).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_edition_changes', function (Blueprint $table): void {
            $table->dropForeign(['payroll_period_id']);
        });
        Schema::table('payroll_edition_changes', function (Blueprint $table): void {
            $table->unsignedBigInteger('payroll_period_id')->nullable()->change();
            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->nullOnDelete();
            $table->index(['ia_id', 'created_at'], 'payroll_edition_changes_ia_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_edition_changes', function (Blueprint $table): void {
            $table->dropIndex('payroll_edition_changes_ia_date_idx');
        });
    }
};
