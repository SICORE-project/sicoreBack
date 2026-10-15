<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('statuts_enseignant') && Schema::hasTable('enseignants')) {
            foreach (DB::table('statuts_enseignant')->get() as $status) {
                $label = mb_strtolower(trim($status->libelle));
                $kind = str_contains($label, 'contractuel') ? 'contractuel' : (str_contains($label, 'vacataire') ? 'vacataire' : null);
                if ($kind && Schema::hasColumn('enseignants', 'type_engagement')) {
                    DB::table('enseignants')->where('type_engagement', $kind)->whereNull('statut_enseignant_id')->update(['statut_enseignant_id' => $status->id]);
                }
                if ($kind && Schema::hasTable('corps_enseignant')) {
                    $legacyIds = DB::table('corps_enseignant')->where(function ($query) use ($kind) {
                        $query->whereRaw('LOWER(libelle) LIKE ?', ['%'.$kind.'%'])
                            ->orWhere('code', $kind === 'vacataire' ? 'VAC' : 'PC');
                    })->pluck('id');
                    foreach (['corps_id', 'corps_enseignant_id'] as $column) {
                        if (Schema::hasColumn('enseignants', $column)) {
                            DB::table('enseignants')->whereNull('statut_enseignant_id')->whereIn($column, $legacyIds)
                                ->update(['statut_enseignant_id' => $status->id]);
                        }
                    }
                }
                $official = in_array($label, ['fonctionnaire', 'fonctionnaires'], true) || in_array(strtolower((string) $status->code), ['fonc', 'fonctionnaire'], true);
                if (!$official) {
                    $values = ['corps_id' => null];
                    if (Schema::hasColumn('enseignants', 'corps_enseignant_id')) $values['corps_enseignant_id'] = null;
                    DB::table('enseignants')->where('statut_enseignant_id', $status->id)->update($values);
                }
            }
        }
        if (Schema::hasTable('payroll_elements') && !Schema::hasColumn('payroll_elements', 'application_statut_enseignant_id')) {
            Schema::table('payroll_elements', function (Blueprint $table) {
                $table->unsignedBigInteger('application_statut_enseignant_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_elements', 'application_statut_enseignant_id')) {
            Schema::table('payroll_elements', fn (Blueprint $table) => $table->dropColumn('application_statut_enseignant_id'));
        }
    }
};
