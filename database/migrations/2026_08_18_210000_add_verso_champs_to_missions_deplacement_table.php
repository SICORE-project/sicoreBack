<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->json('visas_route')->nullable()->after('poids_bagages_mobilier');

            // "AVANCE OU COMPTE PERCUS EN ROUTE"
            $table->decimal('visa_avance_indemnite_normale_nombre', 12, 2)->nullable()->after('visas_route');
            $table->decimal('visa_avance_indemnite_normale_taux', 12, 2)->nullable()->after('visa_avance_indemnite_normale_nombre');
            $table->decimal('visa_avance_indemnite_reduite_nombre', 12, 2)->nullable()->after('visa_avance_indemnite_normale_taux');
            $table->decimal('visa_avance_indemnite_reduite_taux', 12, 2)->nullable()->after('visa_avance_indemnite_reduite_nombre');
            $table->decimal('visa_avance_indemnite_partielle_nombre', 12, 2)->nullable()->after('visa_avance_indemnite_reduite_taux');
            $table->decimal('visa_avance_indemnite_partielle_taux', 12, 2)->nullable()->after('visa_avance_indemnite_partielle_nombre');
            $table->decimal('visa_avance_total', 12, 2)->nullable()->after('visa_avance_indemnite_partielle_taux');
            $table->string('visa_avance_payer_somme', 255)->nullable()->after('visa_avance_total');
            $table->string('visa_avance_lieu', 255)->nullable()->after('visa_avance_payer_somme');
            $table->date('visa_avance_date')->nullable()->after('visa_avance_lieu');

            // "REGLEMENT DEFINITIF"
            $table->decimal('reglement_indemnite_normale_nombre', 12, 2)->nullable()->after('visa_avance_date');
            $table->decimal('reglement_indemnite_normale_taux', 12, 2)->nullable()->after('reglement_indemnite_normale_nombre');
            $table->decimal('reglement_indemnite_reduite_nombre', 12, 2)->nullable()->after('reglement_indemnite_normale_taux');
            $table->decimal('reglement_indemnite_reduite_taux', 12, 2)->nullable()->after('reglement_indemnite_reduite_nombre');
            $table->decimal('reglement_indemnite_partielle_nombre', 12, 2)->nullable()->after('reglement_indemnite_reduite_taux');
            $table->decimal('reglement_indemnite_partielle_taux', 12, 2)->nullable()->after('reglement_indemnite_partielle_nombre');
            $table->decimal('reglement_total', 12, 2)->nullable()->after('reglement_indemnite_partielle_taux');
            $table->decimal('reglement_montant_avances', 12, 2)->nullable()->after('reglement_total');
            $table->decimal('reglement_reste_a_payer', 12, 2)->nullable()->after('reglement_montant_avances');
            $table->string('reglement_arrete_somme', 255)->nullable()->after('reglement_reste_a_payer');
            $table->string('reglement_lieu', 255)->nullable()->after('reglement_arrete_somme');
            $table->date('reglement_date')->nullable()->after('reglement_lieu');

            // "OBSERVATIONS" (colonne libre tout à droite du tableau verso)
            $table->text('observations')->nullable()->after('reglement_date');
        });
    }

    public function down(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->dropColumn([
                'visas_route',
                'visa_avance_indemnite_normale_nombre',
                'visa_avance_indemnite_normale_taux',
                'visa_avance_indemnite_reduite_nombre',
                'visa_avance_indemnite_reduite_taux',
                'visa_avance_indemnite_partielle_nombre',
                'visa_avance_indemnite_partielle_taux',
                'visa_avance_total',
                'visa_avance_payer_somme',
                'visa_avance_lieu',
                'visa_avance_date',
                'reglement_indemnite_normale_nombre',
                'reglement_indemnite_normale_taux',
                'reglement_indemnite_reduite_nombre',
                'reglement_indemnite_reduite_taux',
                'reglement_indemnite_partielle_nombre',
                'reglement_indemnite_partielle_taux',
                'reglement_total',
                'reglement_montant_avances',
                'reglement_reste_a_payer',
                'reglement_arrete_somme',
                'reglement_lieu',
                'reglement_date',
                'observations',
            ]);
        });
    }
};
