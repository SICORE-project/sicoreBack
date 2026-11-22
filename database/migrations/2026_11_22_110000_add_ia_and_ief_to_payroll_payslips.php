<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'IA et l'IEF sont figées sur le bulletin au moment du calcul : si un
 * enseignant change d'affectation, ses anciens bulletins restent dans les
 * rapports de l'IA qui l'a payé.
 *
 * Reprise des bulletins existants : on ne connaît pas l'affectation passée,
 * on recopie donc l'affectation actuelle de l'enseignant (IA déduite de l'IEF).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_payslips', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_payslips', 'ia_id')) {
                $table->foreignId('ia_id')->nullable()->after('enseignant_id')->constrained('ias')->restrictOnDelete();
            }
            if (! Schema::hasColumn('payroll_payslips', 'ief_id')) {
                $table->foreignId('ief_id')->nullable()->after('ia_id')->constrained('iefs')->restrictOnDelete();
            }
            $table->index(['payroll_period_id', 'ia_id']);
        });

        $teachers = DB::table('enseignants as e')
            ->leftJoin('iefs as f', 'f.id', '=', 'e.ief_id')
            ->whereIn('e.id', DB::table('payroll_payslips')->whereNull('ia_id')->select('enseignant_id'))
            ->get(['e.id', 'e.ief_id', DB::raw('COALESCE(f.ia_id, e.ia_id) as ia_id')]);
        foreach ($teachers as $teacher) {
            DB::table('payroll_payslips')->where('enseignant_id', $teacher->id)->whereNull('ia_id')
                ->update(['ia_id' => $teacher->ia_id, 'ief_id' => $teacher->ief_id]);
        }
    }

    public function down(): void
    {
        Schema::table('payroll_payslips', function (Blueprint $table): void {
            $table->dropIndex(['payroll_period_id', 'ia_id']);
            $table->dropConstrainedForeignId('ief_id');
            $table->dropConstrainedForeignId('ia_id');
        });
    }
};
