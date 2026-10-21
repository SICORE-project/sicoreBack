<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->string('grade_emploi', 255)->nullable()->after('beneficiaire_id');

            $table->string('heure_depart', 10)->nullable()->after('date_depart');

            $table->string('ordre_service_numero', 100)->nullable()->after('moyen_transport');
            $table->date('ordre_service_date')->nullable()->after('ordre_service_numero');
            $table->string('accompagne_de', 255)->nullable()->after('ordre_service_date');
            $table->string('groupe', 50)->nullable()->after('accompagne_de');
            $table->text('itineraire')->nullable()->after('groupe');
            $table->decimal('poids_bagages_kg', 8, 2)->nullable()->after('itineraire');

            $table->string('delivre_par', 255)->nullable()->after('poids_bagages_kg');
            $table->date('date_emission_fiche')->nullable()->after('delivre_par');

            // Tableau "Décompte des avances au départ" : Nombre x Taux par
            // ligne (Frais de voyage/transport, Indemnité journalière
            // normale/réduite/partielle) — même structure que le papier.
            $table->decimal('avance_frais_transport_nombre', 10, 2)->nullable()->after('date_emission_fiche');
            $table->decimal('avance_frais_transport_taux', 12, 2)->nullable()->after('avance_frais_transport_nombre');
            $table->decimal('avance_indemnite_normale_nombre', 10, 2)->nullable()->after('avance_frais_transport_taux');
            $table->decimal('avance_indemnite_normale_taux', 12, 2)->nullable()->after('avance_indemnite_normale_nombre');
            $table->decimal('avance_indemnite_reduite_nombre', 10, 2)->nullable()->after('avance_indemnite_normale_taux');
            $table->decimal('avance_indemnite_reduite_taux', 12, 2)->nullable()->after('avance_indemnite_reduite_nombre');
            $table->decimal('avance_indemnite_partielle_nombre', 10, 2)->nullable()->after('avance_indemnite_reduite_taux');
            $table->decimal('avance_indemnite_partielle_taux', 12, 2)->nullable()->after('avance_indemnite_partielle_nombre');
            $table->decimal('avance_total', 12, 2)->nullable()->after('avance_indemnite_partielle_taux');
            $table->decimal('avance_versee', 12, 2)->nullable()->after('avance_total');
        });
    }

    public function down(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->dropColumn([
                'grade_emploi',
                'heure_depart',
                'ordre_service_numero',
                'ordre_service_date',
                'accompagne_de',
                'groupe',
                'itineraire',
                'poids_bagages_kg',
                'delivre_par',
                'date_emission_fiche',
                'avance_frais_transport_nombre',
                'avance_frais_transport_taux',
                'avance_indemnite_normale_nombre',
                'avance_indemnite_normale_taux',
                'avance_indemnite_reduite_nombre',
                'avance_indemnite_reduite_taux',
                'avance_indemnite_partielle_nombre',
                'avance_indemnite_partielle_taux',
                'avance_total',
                'avance_versee',
            ]);
        });
    }
};
