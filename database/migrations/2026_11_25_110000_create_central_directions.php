<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Directions centrales fixes : chaque rôle central y est rattaché
 * (super_admin -> CI, admin -> DAGE, agents DRH -> DRH, DECPC -> DECPC).
 * Crée seulement celles qui manquent ; une direction déjà présente n'est pas modifiée.
 */
return new class extends Migration
{
    private const DIRECTIONS = [
        'CI' => 'Cellule Informatique',
        'DAGE' => 'Direction de l’Administration générale et de l’Équipement',
        'DRH' => 'Direction des ressources humaines',
        'DECPC' => 'Direction des Examens, Concours Professionnels et Certifications',
    ];

    public function up(): void
    {
        foreach (self::DIRECTIONS as $type => $libelle) {
            // Une direction nationale active de ce type existe déjà : on n'y touche pas.
            $existe = DB::table('lieu_de_services')
                ->where('type', $type)
                ->where('perimetre', 'national')
                ->whereNull('deleted_at')
                ->exists();

            if ($existe) {
                continue;
            }

            $ligne = DB::table('lieu_de_services')->where('code', $type)->first();

            if ($ligne) {
                // Le code existe mais la direction a été supprimée ou mal typée : on la remet en état.
                DB::table('lieu_de_services')->where('id', $ligne->id)->update([
                    'type' => $type,
                    'perimetre' => 'national',
                    'ia_id' => null,
                    'ief_id' => null,
                    'est_actif' => true,
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('lieu_de_services')->insert([
                'code' => $type,
                'libelle' => $libelle,
                'type' => $type,
                'perimetre' => 'national',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Rien à défaire : ces directions peuvent déjà avoir des utilisateurs rattachés.
    }
};
