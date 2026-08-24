<?php

namespace Database\Seeders;

use App\Models\Indemnite\piece_justificatives;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Réinitialise complètement les données de démonstration du module
 * Indemnités (demande utilisatrice : "vide toutes les tables et créer des
 * seeders pour chaque table... 10 convocations avec des centres... pièces
 * justificatives... 10 membres et président de jury et président de
 * centre... du contenu dans chaque vue, des données cohérentes et
 * fiables").
 *
 * PÉRIMÈTRE (confirmé avec l'utilisatrice) : uniquement les tables du
 * module Indemnités — comptes utilisateurs/rôles/permissions INTACTS,
 * pas de déconnexion.
 *
 * Ordre d'exécution strict (chaque étape dépend de la précédente) :
 *   1. Vidage des tables (dans n'importe quel ordre : ces tables sont en
 *      MyISAM, donc sans contrainte de clé étrangère réellement appliquée
 *      — voir Convocations::booted(), déjà documenté dans le code pour
 *      cette même raison).
 *   2. Génération des fichiers PDF factices (disque "public") — doivent
 *      exister AVANT que PieceJustificativeSeeder ne les référence.
 *   3. EnseignantSeeder (20 enseignants — 10 "direction" + 10 "membres").
 *   4. TypeIndemniteSeeder (référentiel, indépendant des convocations).
 *   5. ConvocationSeeder (10 convocations).
 *   6. ConvocationCentreSeeder (1 centre + 1-2 métiers par convocation).
 *   7. ConvocationEnseignantSeeder (4 membres du jury par convocation).
 *   8. PieceJustificativeSeeder (dossier de 6 pièces par personne).
 *   9. FraisDeplacementSeeder (fiche de déplacement pour ~70% des membres
 *      au dossier complet — relit 8).
 *  10. IndemniteCorrectionSeeder / IndemniteSurveillanceSeeder.
 *  11. IndemniteSeeder (relit 4 et 10), EtatPaieIndemniteSeeder (relit
 *      5-10) — doivent rester en dernier, ils recomposent des lignes à
 *      partir des tables déjà remplies ci-dessus.
 *
 * Utilisation : php artisan db:seed --class=IndemnitesDemoSeeder
 */
class IndemnitesDemoSeeder extends Seeder
{
    /**
     * Ordre indifférent pour la troncature elle-même (MyISAM, pas de FK
     * réelle) mais gardé "enfants avant parents" par lisibilité/habitude.
     */
    private const TABLES_A_VIDER = [
        'indemnites_correction',
        'indemnites_surveillance',
        'indemnites',
        'etat_paie_indemnites',
        'type_indemnites',
        // justificatifs_frais_deplacement/lignes_frais_deplacement avant
        // missions_deplacement (rien n'imposait cet ordre, MyISAM, mais
        // par habitude "enfants avant parents"). Ces 3 tables contenaient
        // des lignes orphelines (convocation_id d'un ancien jeu de
        // donnees avant le premier reset, ids 57/60/66/72 inexistants
        // desormais) — les vider ici les nettoie au passage.
        'justificatifs_frais_deplacement',
        'lignes_frais_deplacement',
        'missions_deplacement',
        'piece_justificatives',
        'convocation_enseignant',
        'convocation_centre_metiers',
        'convocation_envois',
        'convocation_centres',
        'convocations',
        'enseignants',
    ];

    public function run(): void
    {
        $this->viderLesTables();
        $this->genererFichiersPlaceholder();

        $this->call([
            EnseignantSeeder::class,
            TypeIndemniteSeeder::class,
            ConvocationSeeder::class,
            ConvocationCentreSeeder::class,
            ConvocationEnseignantSeeder::class,
            PieceJustificativeSeeder::class,
            FraisDeplacementSeeder::class,
            IndemniteCorrectionSeeder::class,
            IndemniteSurveillanceSeeder::class,
            // Les deux suivants relisent les tables ci-dessus (deja
            // remplies) pour construire des lignes coherentes — doivent
            // rester en dernier.
            IndemniteSeeder::class,
            EtatPaieIndemniteSeeder::class,
        ]);

        $this->command?->info('Données de démonstration du module Indemnités régénérées avec succès.');
    }

    private function viderLesTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES_A_VIDER as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Un petit PDF valide (pas juste un fichier texte renommé) par type de
     * pièce, stocké une seule fois sur le disque "public" et réutilisé par
     * TOUTES les pièces déposées de ce type (voir PieceJustificativeSeeder)
     * — les boutons "Voir / Télécharger" fonctionnent vraiment pour les
     * données de démonstration.
     */
    private function genererFichiersPlaceholder(): void
    {
        $libelles = piece_justificatives::TYPES;

        foreach ($libelles as $type => $label) {
            $chemin = 'pieces-justificatives/demo/'.$type.'.pdf';

            Storage::disk('public')->put($chemin, $this->construirePdfMinimal($label));
        }
    }

    private function construirePdfMinimal(string $titre): string
    {
        $texte = 'Document de demonstration - '.$titre;
        // Echappe parentheses/antislash, seuls caracteres speciaux dans une
        // chaine PDF littérale (Tj) — sinon un titre contenant "(" casserait
        // le flux de contenu.
        $texteEchappe = addcslashes($texte, '()\\');

        $flux = "BT /F1 14 Tf 40 140 Td ({$texteEchappe}) Tj ET";
        $longueurFlux = strlen($flux);

        return "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 400 200]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            ."4 0 obj<</Length {$longueurFlux}>>\nstream\n{$flux}\nendstream\nendobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n"
            ."trailer<</Size 6/Root 1 0 R>>\n"
            .'%%EOF';
    }
}
