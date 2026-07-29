<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->foreign('situation_familiale_id')->references('id')->on('situations_familiales')->onDelete('set null');
            $table->foreign('corps_id')->references('id')->on('corps_enseignant')->onDelete('cascade');
            $table->foreign('diplome_id')->references('id')->on('diplomes')->onDelete('set null');
            $table->foreign('discipline_id')->references('id')->on('disciplines')->onDelete('set null');
            $table->foreign('specialite_id')->references('id')->on('specialites')->onDelete('set null');
            $table->foreign('categorie_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('lieu_service_id')->references('id')->on('lieu_de_services')->onDelete('cascade');
            $table->foreign('lieu_paiement_id')->references('id')->on('lieux_paiement')->onDelete('set null');
            $table->foreign('ief_id')->references('id')->on('iefs')->onDelete('cascade');
            $table->foreign('ia_id')->references('id')->on('ias')->onDelete('set null');
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
            $table->dropForeign(['diplome_id']);
            $table->dropForeign(['discipline_id']);
            $table->dropForeign(['specialite_id']);
            $table->dropForeign(['categorie_id']);
            $table->dropForeign(['lieu_service_id']);
            $table->dropForeign(['lieu_paiement_id']);
            $table->dropForeign(['ief_id']);
            $table->dropForeign(['ia_id']);
            // $table->dropForeign(['nationalite_id']); // <-- SUPPRIMÉ
            $table->dropForeign(['statut_enseignant_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
    }
};