<?php

namespace Database\Seeders;

use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\Etat_paie_indemnites;
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
 * Limité aux convocations "envoyee" (déjà traitées, cf. ConvocationSeeder) :
 * générer un état de paie pour une session encore en brouillon n'aurait pas
 * de sens narratif.
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
 * ConvocationEnseignantSeeder, IndemniteCorrectionSeeder,
 * IndemniteSurveillanceSeeder ET FraisDeplacementSeeder (a besoin de leurs
 * données réelles — y compris les vraies fiches missions_deplacement —
 * pour construire des lignes "details" cohérentes).
 *
 * Utilisation : php artisan db:seed --class=EtatPaieIndemniteSeeder
 */
class EtatPaieIndemniteSeeder extends Seeder
{
    private const UTILISATEUR_ID = 1;

    public function run(): void
    {
        $convocations = Convocations::with('centres')
            ->where('statut', 'envoyee')
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

            // Lignes "details" construites a partir des VRAIES fiches de
            // deplacement deja creees par FraisDeplacementSeeder — montant
            // = missions_deplacement.montant_calcule reel, jamais invente.
            // Avant ce correctif, un montant synthetique etait attribue au
            // chef de centre/president du jury meme quand aucune fiche
            // n'existait reellement pour eux sur cette convocation : la
            // modale "Voir état" affichait alors un montant different de
            // celui (0 F) affiche par la liste live des membres, qui lit
            // elle la vraie fiche (voir EtatPaieIndemnitesController::
            // lignesFraisDeplacement() cote front) — incoherence constatee
            // par l'utilisatrice entre les deux.
            $fichesDeplacement = DB::table('missions_deplacement')
                ->join('enseignants', 'enseignants.id', '=', 'missions_deplacement.beneficiaire_id')
                ->where('missions_deplacement.convocation_id', $convocation->id)
                ->select(
                    'missions_deplacement.beneficiaire_id',
                    'missions_deplacement.montant_calcule',
                    'enseignants.nom',
                    'enseignants.prenom',
                    'enseignants.categorie_personnel'
                )
                ->get();

            if ($fichesDeplacement->isNotEmpty() && $centre) {
                // Fonction de chaque beneficiaire pour l'affichage — meme
                // priorite que FraisDeplacementController::construireLigne()
                // cote back : role dedie du centre (chef/president), sinon
                // fonction du pivot, sinon "Membre du jury" par defaut.
                $pivotFonctions = DB::table('convocation_enseignant')
                    ->where('convocation_id', $convocation->id)
                    ->pluck('fonction', 'enseignant_id');

                $details = $fichesDeplacement->map(function ($ligne) use ($centre, $pivotFonctions) {
                    $fonction = match ((int) $ligne->beneficiaire_id) {
                        (int) $centre->chef_centre_id => 'Chef de centre',
                        (int) $centre->president_jury_id => 'Président de jury',
                        default => $pivotFonctions[$ligne->beneficiaire_id] ?? 'Membre du jury',
                    };

                    return $this->ligneDetail(
                        (object) [
                            'enseignant_id' => $ligne->beneficiaire_id,
                            'nom' => $ligne->nom,
                            'prenom' => $ligne->prenom,
                            'categorie_personnel' => $ligne->categorie_personnel,
                            'montant' => $ligne->montant_calcule,
                        ],
                        fonction: $fonction,
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

        // Seules des convocations "envoyee" arrivent ici (voir run()) : leur
        // cycle de paie est encore en cours, jamais deja finalise.
        $statut = 'brouillon';

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
