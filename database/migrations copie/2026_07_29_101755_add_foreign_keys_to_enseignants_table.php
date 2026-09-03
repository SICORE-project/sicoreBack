<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL keeps constraints applied before a later ALTER TABLE failure.
        // This lets a previously interrupted migration resume safely.
        if (DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', 'enseignants')
            ->where('constraint_name', 'enseignants_situation_familiale_id_foreign')
            ->exists()) {
            return;
        }

        Schema::table('enseignants', function (Blueprint $table) {
            $table->foreign('situation_familiale_id')->references('id')->on('situations_familiales')->onDelete('set null');
            $table->foreign('corps_id')->references('id')->on('corps_enseignant')->onDelete('cascade');
            $table->foreign('specialite_id')->references('id')->on('specialites')->onDelete('set null');
            $table->foreign('lieu_service_id')->references('id')->on('lieu_de_services')->onDelete('cascade');
            $table->foreign('lieu_paiement_id')->references('id')->on('lieux_paiement')->onDelete('set null');
            // $table->foreign('nationalite_id')->references('id')->on('nationalites')->onDelete('set null'); // <-- SUPPRIMÉ
            $table->foreign('statut_enseignant_id')->references('id')->on('statuts_enseignant')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropForeign(['situation_familiale_id']);
            $table->dropForeign(['corps_id']);
            $table->dropForeign(['specialite_id']);
            $table->dropForeign(['lieu_service_id']);
            $table->dropForeign(['lieu_paiement_id']);
            // $table->dropForeign(['nationalite_id']); // <-- SUPPRIMÉ
            $table->dropForeign(['statut_enseignant_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
    }
};
