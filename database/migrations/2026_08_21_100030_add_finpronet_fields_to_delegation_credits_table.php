<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aligne l'en-tete de la delegation sur l'ecran FINPRONET frmDelegation.aspx :
     * ajout de la periode de paie, et passage de structure/service en nullable
     * le temps de la bascule vers l'axe corps d'enseignant / IA / IEF.
     */
    public function up(): void
    {
        Schema::table('delegation_credits', function (Blueprint $table) {
            $table->string('periode_paie')->nullable()->after('annee_academique');
        });

        Schema::table('delegation_credits', function (Blueprint $table) {
            $table->dropForeign(['structure_id']);
            $table->dropForeign(['service_id']);
        });

        Schema::table('delegation_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('structure_id')->nullable()->change();
            $table->unsignedBigInteger('service_id')->nullable()->change();
        });

        Schema::table('delegation_credits', function (Blueprint $table) {
            $table->foreign('structure_id')->references('id')->on('structures')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delegation_credits', function (Blueprint $table) {
            $table->dropForeign(['structure_id']);
            $table->dropForeign(['service_id']);
        });

        Schema::table('delegation_credits', function (Blueprint $table) {
            $table->unsignedBigInteger('structure_id')->nullable(false)->change();
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
        });

        Schema::table('delegation_credits', function (Blueprint $table) {
            $table->foreign('structure_id')->references('id')->on('structures')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->dropColumn('periode_paie');
        });
    }
};
