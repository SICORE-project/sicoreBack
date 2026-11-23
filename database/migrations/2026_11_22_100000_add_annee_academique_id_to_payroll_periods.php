<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque période de paie est rattachée à son année académique par une clé
 * étrangère, au lieu d'un rattachement recalculé à partir des dates.
 *
 * Reprise : règle unique de PayrollMonthResolver — un mois appartient à
 * l'année dont l'intervalle [date_debut, date_fin] le chevauche. Une période
 * qui ne chevauche aucune année, ou en chevauche plusieurs, reste sans année
 * (NULL) et doit être corrigée à la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payroll_periods', 'annee_academique_id')) {
            Schema::table('payroll_periods', function (Blueprint $table): void {
                $table->foreignId('annee_academique_id')->nullable()->after('end_date')
                    ->constrained('annee_academiques')->restrictOnDelete();
            });
        }

        foreach (DB::table('payroll_periods')->whereNull('annee_academique_id')->get() as $period) {
            $years = DB::table('annee_academiques')
                ->whereDate('date_debut', '<=', $period->end_date)
                ->whereDate('date_fin', '>=', $period->start_date)
                ->pluck('id');

            if ($years->count() === 1) {
                DB::table('payroll_periods')->where('id', $period->id)
                    ->update(['annee_academique_id' => $years->first()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_periods', 'annee_academique_id')) {
            Schema::table('payroll_periods', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('annee_academique_id');
            });
        }
    }
};
