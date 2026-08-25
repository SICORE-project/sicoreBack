<?php

namespace Database\Seeders;

use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\piece_justificatives;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dossier de pièces justificatives (6 types — voir piece_justificatives::TYPES)
 * pour CHAQUE personne apparaissant sur une convocation : chef de centre,
 * président du jury (convocation_centres) ET membres du jury
 * (convocation_enseignant) — même règle que
 * PiecesJustificativesController::construireMembres() côté front, qui
 * traite ces 3 rôles à égalité (chacun dépose ses propres pièces).
 *
 * Le taux de complétude des dossiers suit le statut de la convocation
 * (donnée cohérente, pas aléatoire pure) : une convocation envoyée a des
 * dossiers presque complets, une convocation encore en brouillon a des
 * dossiers à peine entamés — comme un vrai suivi de dépôt de pièces qui
 * progresse avec l'avancement de la session.
 *
 * Chaque pièce déposée pointe vers un fichier PDF factice réel (voir
 * IndemnitesDemoSeeder::genererFichiersPlaceholder(), stocké sur le disque
 * "public") : les boutons "Voir / Télécharger" fonctionnent vraiment,
 * pas juste un nom de fichier sans contenu derrière.
 *
 * Doit être exécuté APRÈS ConvocationCentreSeeder ET ConvocationEnseignantSeeder,
 * et APRÈS IndemnitesDemoSeeder::genererFichiersPlaceholder() (les fichiers
 * doivent déjà exister sur le disque avant d'être référencés ici).
 *
 * Utilisation : php artisan db:seed --class=PieceJustificativeSeeder
 */
class PieceJustificativeSeeder extends Seeder
{
    /**
     * Profil de complétude (probabilité qu'UNE pièce donnée soit déjà
     * déposée) par statut de convocation — voir le commentaire de classe.
     * Volontairement très élevé pour envoyee : "complet" exige les
     * 6 pièces à la fois (voir tirerStatut()) — un taux par pièce trop bas
     * laisserait presque aucun dossier réellement complet, alors qu'une
     * session déjà envoyée doit, côté récit, avoir déjà fini ce travail (et
     * alimenter des bénéficiaires "éligibles" sur la page Frais de
     * déplacement, qui exige un dossier 100% complet et sans rejet).
     */
    private const TAUX_DEPOT_PAR_STATUT = [
        'envoyee' => 0.94,
        'emise' => 0.6,
        'brouillon' => 0.25,
    ];

    /**
     * Probabilité qu'une pièce déposée soit rejetée, par statut de
     * convocation — une session déjà envoyée a eu le temps de corriger ses
     * rejets (peu en restent), une session encore en cours en a
     * proportionnellement plus.
     */
    private const TAUX_REJET_PAR_STATUT = [
        'envoyee' => 0.05,
        'emise' => 0.15,
        'brouillon' => 0.15,
    ];

    private const DEPOSITAIRE_ID = 1;

    public function run(): void
    {
        $chemins = $this->cheminsPlaceholder();

        if (! $chemins) {
            $this->command?->warn('PieceJustificativeSeeder : fichiers placeholder introuvables — exécutez IndemnitesDemoSeeder (pas ce seeder isolément) pour les générer.');

            return;
        }

        $convocations = Convocations::with(['centres', 'enseignants'])->orderBy('id')->get();

        foreach ($convocations as $convocation) {
            $taux = self::TAUX_DEPOT_PAR_STATUT[$convocation->statut] ?? 0.5;

            foreach ($this->personnesDeLaConvocation($convocation) as $personne) {
                [$enseignantId, $centreId] = $personne;

                foreach (piece_justificatives::TYPES as $type => $label) {
                    $depose = mt_rand() / mt_getrandmax() < $taux;

                    if (! $depose) {
                        continue;
                    }

                    $dateDepot = $convocation->date_emission
                        ? $convocation->date_emission->copy()->addDays(random_int(1, 6))
                        : now();

                    $statut = $this->tirerStatut(self::TAUX_REJET_PAR_STATUT[$convocation->statut] ?? 0.1);

                    piece_justificatives::create([
                        'type' => $type,
                        'convocation_id' => $convocation->id,
                        'enseignant_id' => $enseignantId,
                        'centre_id' => $centreId,
                        'statut' => $statut,
                        'date_depot' => $dateDepot,
                        'chemin' => $chemins[$type],
                        'nom_original' => Str::slug($label).'-'.$enseignantId.'.pdf',
                        'mime_type' => 'application/pdf',
                        'taille' => Storage::disk('public')->size($chemins[$type]),
                        'depositaire_id' => self::DEPOSITAIRE_ID,
                        'conforme' => $statut === 'valide' ? true : ($statut === 'rejete' ? false : null),
                        'commentaire_rejet' => $statut === 'rejete' ? 'Document illisible — merci de redéposer un scan plus net.' : null,
                        'verifie_par' => $statut !== 'depose' ? self::DEPOSITAIRE_ID : null,
                        'verifie_at' => $statut !== 'depose' ? $dateDepot->copy()->addDay() : null,
                        'valide_par' => $statut === 'valide' ? self::DEPOSITAIRE_ID : null,
                        'valide_at' => $statut === 'valide' ? $dateDepot->copy()->addDay() : null,
                    ]);
                }
            }
        }
    }

    /**
     * Chef de centre + président du jury (1 chacun, via convocation_centres)
     * + tous les membres du pivot convocation_enseignant — dédupliqués par
     * enseignant_id (un même enseignant ne doit avoir qu'UN SEUL dossier
     * par convocation, même s'il cumule plusieurs rôles).
     *
     * @return list<array{0: int, 1: int|null}> paires [enseignant_id, centre_id]
     */
    private function personnesDeLaConvocation(Convocations $convocation): array
    {
        $personnes = [];

        foreach ($convocation->centres as $centre) {
            if ($centre->chef_centre_id) {
                $personnes[$centre->chef_centre_id] = [$centre->chef_centre_id, $centre->id];
            }

            if ($centre->president_jury_id) {
                $personnes[$centre->president_jury_id] = [$centre->president_jury_id, $centre->id];
            }
        }

        foreach ($convocation->enseignants as $enseignant) {
            $personnes[$enseignant->id] ??= [$enseignant->id, $enseignant->pivot->centre_id];
        }

        return array_values($personnes);
    }

    /**
     * $tauxRejet (0-1) de rejetées ; le reste se répartit 70% validées /
     * 30% déposées (en attente de vérification) — repartis sur les pièces
     * effectivement déposées (le taux de dépôt lui-même est géré
     * séparément via TAUX_DEPOT_PAR_STATUT).
     */
    private function tirerStatut(float $tauxRejet): string
    {
        $tirage = mt_rand() / mt_getrandmax();

        if ($tirage < $tauxRejet) {
            return 'rejete';
        }

        $tirageRestant = mt_rand() / mt_getrandmax();

        return $tirageRestant < 0.7 ? 'valide' : 'depose';
    }

    /**
     * @return array<string, string>|null chemin (disque "public") par type,
     *                                     ou null si pas encore genere.
     */
    private function cheminsPlaceholder(): ?array
    {
        $chemins = [];

        foreach (array_keys(piece_justificatives::TYPES) as $type) {
            $chemin = 'pieces-justificatives/demo/'.$type.'.pdf';

            if (! Storage::disk('public')->exists($chemin)) {
                return null;
            }

            $chemins[$type] = $chemin;
        }

        return $chemins;
    }
}
