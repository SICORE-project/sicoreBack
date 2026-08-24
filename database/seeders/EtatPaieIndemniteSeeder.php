<?php

namespace Database\Seeders;

use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\Etat_paie_indemnites;
use App\Models\Parametrage\Enseignant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * États de paie de démonstration — un par (convocation déjà traitée x
 * type de domaine) réellement présent en base, pour que le bouton "Voir
 * état(s) existants" de la page États de paie renvoie de vrais résultats
 * quand on filtre par un objet/session/centre issus des convocations déjà
 * seedées (le filtre "objet" interroge perimetre->objet en JSON — voir
 * EtatPaieIndemnitesController::index() côté back — donc cette valeur DOIT
 * correspondre à une convocation réelle pour être atteignable depuis
 * l'interface).
 *
 * Limité aux convocations "cloturee"/"envoyee" (déjà traitées, cf.
 * ConvocationSeeder) : générer un état de paie pour une session encore en
 * brouillon n'aurait pas de sens narratif.
 *
 * NB pour l'utilisatrice : les 4 cartes de stats en haut de la page
 * États de paie (générées/validées/en attente/transmises) sont câblées en
 * dur à 0 côté contrôleur front (voir le commentaire "pas encore
 * implémenté" dans EtatPaieIndemnitesController::index()) — elles
 * resteront à 0 quel que soit ce qui est seedé ici, ce n'est pas un bug de
 * ce seeder. Le contenu réellement visible est la modale "Voir état(s)
 * existants" après avoir choisi type + objet/session.
 *
 * Doit être exécuté APRÈS ConvocationSeeder, ConvocationCentreSeeder,
 * ConvocationEnseignantSeeder, IndemniteCorrectionSeeder ET
 * IndemniteSurveillanceSeeder (a besoin de leurs données réelles pour
 * construire des lignes "details" cohérentes).
 *
 * Utilisation : php artisan db:seed --class=EtatPaieIndemniteSeeder
 */
class EtatPaieIndemniteSeeder extends Seeder
{
    private const UTILISATEUR_ID = 1;

    public function run(): void
    {
        $convocations = Convocations::with('centres')
            ->whereIn('statut', ['cloturee', 'envoyee'])
            ->orderBy('id')
            ->get();

        foreach ($convocations as $convocation) {
            $centre = $convocation->centres->first();

            $lignesCorrection = DB::table('indemnites_correction')
                ->join('enseignants', 'enseignants.id', '=', 'indemnites_correction.enseignant_id')
                ->where('indemnites_correction.convocation_id', $convocation->id)
                ->select('indemnites_correction.*', 'enseignants.nom', 'enseignants.prenom', 'enseignants.categorie_personnel')
                ->get();

            if ($lignesCorrection->isNotEmpty()) {
                $this->creerEtat($convocation, $centre, 'indemnite_correction', $lignesCorrection->map(
                    fn ($l) => $this->ligneDetail($l, fonction: 'Correction', metier: $l->metier)
                )->all());
            }

            $lignesSurveillance = DB::table('indemnites_surveillance')
                ->join('enseignants', 'enseignants.id', '=', 'indemnites_surveillance.enseignant_id')
                ->where('indemnites_surveillance.convocation_id', $convocation->id)
                ->select('indemnites_surveillance.*', 'enseignants.nom', 'enseignants.prenom', 'enseignants.categorie_personnel')
                ->get();

            if ($lignesSurveillance->isNotEmpty()) {
                $this->creerEtat($convocation, $centre, 'indemnite_surveillance', $lignesSurveillance->map(
                    fn ($l) => $this->ligneDetail($l, fonction: 'Surveillant', metier: $l->metier)
                )->all());
            }

            // Pas de fiche de frais de deplacement reellement creee (hors
            // perimetre de ce jeu de seeders) : lignes "details" construites
            // directement a partir du chef de centre/president du jury de
            // CE centre, avec un montant plausible mais synthetique.
            if ($centre && ($centre->chef_centre_id || $centre->president_jury_id)) {
                $responsables = Enseignant::whereIn('id', array_filter([$centre->chef_centre_id, $centre->president_jury_id]))->get();

                $details = $responsables->map(function (Enseignant $enseignant) use ($centre) {
                    $estChef = $enseignant->id === $centre->chef_centre_id;

                    return $this->ligneDetail(
                        (object) [
                            'enseignant_id' => $enseignant->id,
                            'nom' => $enseignant->nom,
                            'prenom' => $enseignant->prenom,
                            'categorie_personnel' => $enseignant->categorie_personnel,
                            'montant' => random_int(15, 45) * 1000,
                        ],
                        fonction: $estChef ? 'Chef de centre' : 'Président du jury',
                        metier: null,
                    );
                })->all();

                $this->creerEtat($convocation, $centre, 'frais_deplacement', $details);
            }
        }
    }

    private function ligneDetail(object $ligne, string $fonction, ?string $metier): array
    {
        return [
            'id' => $ligne->enseignant_id,
            'nom' => $ligne->nom,
            'prenom' => $ligne->prenom,
            'categorie_personnel' => $ligne->categorie_personnel,
            'fonction' => $fonction,
            'metier' => $metier,
            'montant' => (float) $ligne->montant,
            'code_banque' => 'SN'.random_int(100, 999),
            'code_guichet' => (string) random_int(10000, 99999),
            'numero_compte_bancaire' => (string) random_int(100000000000, 999999999999),
            'cle_rib' => (string) random_int(10, 99),
        ];
    }

    private function creerEtat(Convocations $convocation, $centre, string $type, array $details): void
    {
        $totalMontant = array_sum(array_column($details, 'montant'));

        // Une session deja cloturee a, narrativement, deja fini son cycle
        // de paie (valide) ; une session juste envoyee est encore en
        // brouillon — coherent avec ConvocationSeeder.
        $statut = $convocation->statut === 'cloturee' ? 'valide' : 'brouillon';

        Etat_paie_indemnites::create([
            'reference' => 'EP-'.strtoupper(Str::random(8)),
            'type' => $type,
            'utilisateur_id' => self::UTILISATEUR_ID,
            'beneficiaire_id' => null,
            'date_generation' => $convocation->date_fin ?? now(),
            'periode_debut' => $convocation->date_debut,
            'periode_fin' => $convocation->date_fin,
            'lieu_examen' => $centre?->centre,
            'session' => $convocation->session,
            'perimetre' => array_filter([
                'type' => $type,
                'objet' => $convocation->objet,
                'session' => $convocation->session,
                'centre' => $centre?->centre,
            ]),
            'details' => $details,
            'total_montant' => $totalMontant,
            'statut' => $statut,
            'valide_par' => $statut === 'valide' ? self::UTILISATEUR_ID : null,
            'valide_at' => $statut === 'valide' ? ($convocation->date_fin ?? now()) : null,
        ]);
    }
}
