<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->string('situation_matrimoniale', 20)->nullable();
        });
        DB::table('enseignants')->where('est_en_couple', true)->update(['situation_matrimoniale' => 'marie']);
        DB::table('enseignants')->where('est_en_couple', false)->update(['situation_matrimoniale' => 'celibataire']);
        foreach (['FONC' => 'Fonctionnaire', 'CONT' => 'Contractuel', 'VAC' => 'Vacataire'] as $code => $label) {
            $status = DB::table('statuts_enseignant')->whereRaw('LOWER(libelle) = ?', [strtolower($label)])->first();
            if (!$status) continue;
            $id = $status->id;
            $corpsIds = DB::table('corps_enseignant')->whereRaw('LOWER(TRIM(libelle)) = ?', [strtolower($label)])->pluck('id');
            DB::table('enseignants')->whereNull('statut_enseignant_id')->whereIn('corps_id', $corpsIds)->update(['statut_enseignant_id' => $id]);
        }
    }
    public function down(): void {
        Schema::table('enseignants', fn (Blueprint $table) => $table->dropColumn('situation_matrimoniale'));
    }
};
