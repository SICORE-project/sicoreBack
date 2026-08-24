<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Types d'indemnités de référence (mode de calcul + taux).
 *
 * Ecrit en DB::table() plutôt que via le modèle Eloquent : la classe
 * `App\Models\type_indemnites` vit dans app/Models/indemnite/ mais déclare
 * `namespace App\Models;` (sans le segment "Indemnite") — un décalage avec
 * l'autoload PSR-4 standard (`App\ -> app/`) qui la rend indisponible tant
 * qu'un classmap Composer ne l'a pas répertoriée explicitement. DB::table()
 * évite ce problème et reste fiable quel que soit l'état du cache
 * d'autoload.
 *
 * NB pour l'utilisatrice : aucune page du front actuellement construit
 * (Convocations, Frais de déplacement, Calcul, Calcul-surveillance,
 * Pièces justificatives, États de paie) ne lit cette table — les pages
 * "Calcul"/"Calcul-surveillance" saisissent leur taux librement à chaque
 * calcul (voir IndemniteCorrectionController, commentaire "pas de barème
 * préconfiguré"), et le seul contrôleur qui l'utilise
 * (Api/Indemnites/TypeIndemnitesController) n'a aucun consommateur côté
 * front. Seedée ici pour complétude référentielle et parce que `indemnites.
 * type_indemnite_id` (voir IndemniteSeeder) l'exige en clé étrangère
 * obligatoire — pas parce qu'elle apparaîtra dans une vue.
 *
 * Utilisation : php artisan db:seed --class=TypeIndemniteSeeder
 */
class TypeIndemniteSeeder extends Seeder
{
    public function run(): void
    {
        $maintenant = now();

        $types = [
            [
                'libelle' => 'Indemnité de correction',
                'description' => 'Indemnité versée aux correcteurs de copies, par copie corrigée.',
                'mode_calcul' => 'forfaitaire',
                'montant_forfaitaire' => 500.00,
                'taux_horaire' => null,
                'taux_kilometrique' => null,
            ],
            [
                'libelle' => 'Indemnité de surveillance',
                'description' => 'Indemnité versée aux surveillants de salle, par heure de surveillance.',
                'mode_calcul' => 'horaire',
                'montant_forfaitaire' => null,
                'taux_horaire' => 2000.00,
                'taux_kilometrique' => null,
            ],
            [
                'libelle' => 'Frais de déplacement',
                'description' => "Remboursement des frais de déplacement pour se rendre au centre d'examen.",
                'mode_calcul' => 'kilometrique',
                'montant_forfaitaire' => null,
                'taux_horaire' => null,
                'taux_kilometrique' => 150.00,
            ],
            [
                'libelle' => 'Indemnité de président de jury',
                'description' => "Indemnité forfaitaire versée au président du jury d'une session.",
                'mode_calcul' => 'forfaitaire',
                'montant_forfaitaire' => 50000.00,
                'taux_horaire' => null,
                'taux_kilometrique' => null,
            ],
            [
                'libelle' => 'Indemnité de chef de centre',
                'description' => "Indemnité forfaitaire versée au chef d'un centre d'examen.",
                'mode_calcul' => 'forfaitaire',
                'montant_forfaitaire' => 50000.00,
                'taux_horaire' => null,
                'taux_kilometrique' => null,
            ],
        ];

        foreach ($types as $type) {
            DB::table('type_indemnites')->insert($type + [
                'plafond' => null,
                'actif' => true,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }
    }
}
