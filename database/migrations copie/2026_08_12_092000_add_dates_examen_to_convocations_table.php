<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le formulaire front (create.blade.php, étape 1 "Période de l'examen")
 * collecte date_debut / date_fin / heure_debut en champs obligatoires
 * depuis le début, mais ces colonnes n'ont jamais existé sur
 * `convocations` : la donnée saisie par l'utilisateur était envoyée à
 * l'API puis silencieusement perdue (ni dans $fillable, ni dans
 * StoreConvocationRequest). Cf. resources/views/.../README-livraison.md
 * ("Gap non traité ici").
 *
 * A faire tourner APRES 2026_08_12_091000_change_objet_to_text... (déjà
 * la dernière migration du dossier au moment de l'écriture).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocations', function (Blueprint $table) {
            if (! Schema::hasColumn('convocations', 'date_debut')) {
                $table->date('date_debut')->nullable()->after('date_emission');
            }

            if (! Schema::hasColumn('convocations', 'date_fin')) {
                $table->date('date_fin')->nullable()->after('date_debut');
            }

            if (! Schema::hasColumn('convocations', 'heure_debut')) {
                $table->time('heure_debut')->nullable()->after('date_fin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('convocations', function (Blueprint $table) {
            $table->dropColumn(['date_debut', 'date_fin', 'heure_debut']);
        });
    }
};
