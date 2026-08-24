<?php

namespace Database\Seeders;

use App\Models\Indemnite\Convocations;
use App\Models\Indemnite\JustificatifFraisDeplacement;
use App\Models\Indemnite\MissionDeplacement;
use App\Models\Indemnite\piece_justificatives;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fiches de déplacement (missions_deplacement) réelles pour les membres
 * réellement "éligibles" — même règle exacte que
 * FraisDeplacementController::dossierComplet() côté back (les 6 pièces
 * justificatives présentes et aucune rejetée) — demande utilisatrice :
 * avant ce seeder, la page "Frais de déplacement" affichait des
 * bénéficiaires éligibles mais "fiches_creees" restait toujours à 0.
 *
 * Reproduit fidèlement le calcul réel du montant (voir
 * FraisDeplacementController::determinerGroupe()/calculerMontantDeplacement()/
 * nombreJoursPeriodeExamen()) plutôt qu'un montant inventé : Groupe I/II/III
 * selon l'indice (fonctionnaire) ou le salaire annualisé (contractuel/
 * vacataire), taux × nombre de jours de la période d'examen de la
 * convocation, divisé par 4 quand `lieu_affectation` (voir
 * ConvocationCentreSeeder) correspond à la provenance du bénéficiaire
 * (calculerMontantDeplacement()) — une convocation sur deux affecte "sur
 * place", l'autre moitié affecte ailleurs, pour exercer les deux branches
 * du calcul.
 *
 * Ne crée PAS de fiche pour tout le monde d'éligible (seuls ~70%) : le
 * reste reste "à créer" sur la page liste, pour un état réaliste
 * (mélange "Voir la fiche"/"Créer la fiche").
 *
 * Doit être exécuté APRÈS PieceJustificativeSeeder (a besoin des dossiers
 * complets déjà déposés) ET ConvocationEnseignantSeeder/ConvocationCentreSeeder.
 *
 * Utilisation : php artisan db:seed --class=FraisDeplacementSeeder
 */
class FraisDeplacementSeeder extends Seeder
{
    private const TAUX_PAR_GROUPE = ['I' => 25000, 'II' => 20000, 'III' => 15000];

    private const SEUIL_INDICE_GROUPE_I = 2296;

    private const SEUIL_INDICE_GROUPE_II = 1728;

    private const SEUIL_SALAIRE_ANNUEL_GROUPE_I = 24294500;

    private const SEUIL_SALAIRE_ANNUEL_GROUPE_II = 2109610;

    private const DECLARE_PAR_ID = 1;

    private const MOYENS_TRANSPORT = ['Véhicule personnel', 'Véhicule de service', 'Transport en commun'];

    public function run(): void
    {
        $chemins = $this->cheminsPlaceholder();

        $convocations = Convocations::with(['centres', 'enseignants'])->orderBy('id')->get();

        foreach ($convocations as $convocation) {
            $centre = $convocation->centres->first();

            if (! $centre) {
                continue;
            }

            $jours = $this->nombreJoursPeriodeExamen($convocation);

            foreach ($this->personnesEligibles($convocation) as $personne) {
                // ~70% des eligibles recoivent une fiche : le reste reste
                // "a creer" sur la page liste, pour un etat realiste.
                if (mt_rand(1, 100) > 70) {
                    continue;
                }

                $this->creerFiche($convocation, $centre, $personne, $jours, $chemins);
            }
        }
    }

    /**
     * @return list<array{enseignant_id: int, nom: string, prenom: string, categorie_personnel: ?string, provenance: ?string}>
     */
    private function personnesEligibles(Convocations $convocation): array
    {
        $personnes = [];

        foreach ($convocation->centres as $centre) {
            if ($centre->chef_centre_id) {
                $personnes[$centre->chef_centre_id] = [
                    'enseignant_id' => $centre->chef_centre_id,
                    'categorie_personnel' => $centre->chef_centre_categorie_personnel,
                    'provenance' => $centre->chef_centre_provenance,
                ];
            }

            if ($centre->president_jury_id) {
                $personnes[$centre->president_jury_id] = [
                    'enseignant_id' => $centre->president_jury_id,
                    'categorie_personnel' => $centre->president_jury_categorie_personnel,
                    'provenance' => $centre->president_jury_provenance,
                ];
            }
        }

        foreach ($convocation->enseignants as $enseignant) {
            $personnes[$enseignant->id] ??= [
                'enseignant_id' => $enseignant->id,
                'categorie_personnel' => $enseignant->pivot->categorie_personnel,
                'provenance' => $enseignant->pivot->provenance,
            ];
        }

        // Meme regle exacte que FraisDeplacementController::dossierComplet() :
        // les 6 types presents, aucun rejete.
        $eligibles = [];

        foreach ($personnes as $enseignantId => $donnees) {
            $typesPresents = piece_justificatives::where('convocation_id', $convocation->id)
                ->where('enseignant_id', $enseignantId)
                ->where('statut', '!=', 'rejete')
                ->pluck('type')
                ->unique();

            $complet = count(array_intersect(array_keys(piece_justificatives::TYPES), $typesPresents->all())) === count(piece_justificatives::TYPES);

            if (! $complet) {
                continue;
            }

            $enseignant = \App\Models\Parametrage\Enseignant::find($enseignantId);

            if (! $enseignant) {
                continue;
            }

            $eligibles[] = $donnees + ['nom' => $enseignant->nom, 'prenom' => $enseignant->prenom];
        }

        return $eligibles;
    }

    private function nombreJoursPeriodeExamen(Convocations $convocation): int
    {
        if (! $convocation->date_debut || ! $convocation->date_fin) {
            return 0;
        }

        return (int) $convocation->date_debut->copy()->startOfDay()->diffInDays($convocation->date_fin->copy()->startOfDay()) + 1;
    }

    private function creerFiche(Convocations $convocation, $centre, array $personne, int $jours, array $chemins): void
    {
        $categoriePersonnel = $personne['categorie_personnel'] ?? 'fonctionnaire';
        $indiceAgent = null;
        $salaireGlobalAnnuel = null;

        if ($categoriePersonnel === 'fonctionnaire') {
            // Reparti sur les 3 groupes pour varier les montants calcules.
            $indiceAgent = match (random_int(1, 3)) {
                1 => random_int(2300, 2600),
                2 => random_int(1800, 2200),
                default => random_int(900, 1600),
            };
            $groupe = $this->determinerGroupe($categoriePersonnel, $indiceAgent, null);
        } else {
            $montantMensuel = random_int(80, 300) * 1000;
            $salaireGlobalAnnuel = $montantMensuel * 12;
            $groupe = $this->determinerGroupe($categoriePersonnel, null, $montantMensuel);
        }

        // Meme comparaison que FraisDeplacementController::
        // calculerMontantDeplacement() : lieu d'affectation (commun a
        // toute la convocation, voir ConvocationCentreSeeder) identique a
        // la provenance de CETTE personne -> taux divise par 4 (elle ne se
        // deplace pas vraiment), sinon taux plein.
        $normaliser = fn (?string $valeur) => mb_strtolower(trim((string) $valeur));
        $memeLieu = $convocation->lieu_affectation
            && $personne['provenance']
            && $normaliser($convocation->lieu_affectation) !== ''
            && $normaliser($convocation->lieu_affectation) === $normaliser($personne['provenance']);
        $tauxAjuste = $memeLieu ? $groupe['taux_base'] / 4 : $groupe['taux_base'];

        $montantCalcule = $tauxAjuste * $jours;

        $dateDepart = $convocation->date_debut?->copy()->subDay();
        $dateRetour = $convocation->date_fin;

        $avanceIndemniteNombre = max(1, intdiv($jours, 2));
        $avanceIndemniteTaux = $groupe['taux_base'];
        $avanceTransportTaux = 15000;
        $avanceTotal = $avanceTransportTaux + ($avanceIndemniteNombre * $avanceIndemniteTaux);

        $statut = $this->tirerStatut($convocation->statut);
        $estSolde = in_array($statut, ['rembourse', 'cloture'], true);

        $fiche = MissionDeplacement::create([
            'convocation_id' => $convocation->id,
            'reference' => 'FD-'.$convocation->date_debut?->format('Y').'-'.strtoupper(Str::random(8)),
            'beneficiaire_id' => $personne['enseignant_id'],
            'grade_emploi' => $categoriePersonnel === 'fonctionnaire' ? 'Professeur' : 'Enseignant vacataire',
            'declare_par' => self::DECLARE_PAR_ID,
            'lieu_depart' => $personne['provenance'] ?? 'Dakar',
            'heure_depart' => '06:00',
            'lieu_destination' => $centre->centre,
            'motif' => "Participation au jury — {$convocation->objet}",
            'date_depart' => $dateDepart,
            'ordre_service_numero' => 'OS-'.$convocation->date_debut?->format('Y').'-'.random_int(1000, 9999),
            'ordre_service_date' => $convocation->date_emission,
            'ordre_service_emetteur' => 'Direction des Examens et Concours',
            'itineraire' => ($personne['provenance'] ?? 'Dakar').' — '.$centre->centre,
            'poids_bagages_kg' => 10,
            'date_emission_fiche' => $convocation->date_emission,
            'avance_frais_transport_nombre' => 1,
            'avance_frais_transport_taux' => $avanceTransportTaux,
            'avance_indemnite_normale_nombre' => $avanceIndemniteNombre,
            'avance_indemnite_normale_taux' => $avanceIndemniteTaux,
            'avance_total' => $avanceTotal,
            'avance_versee' => $avanceTotal,
            'date_fait_avance' => $dateDepart,
            'date_retour' => $dateRetour,
            'distance_km' => random_int(5, 250),
            'moyen_transport' => self::MOYENS_TRANSPORT[array_rand(self::MOYENS_TRANSPORT)],
            'statut_agent' => $categoriePersonnel,
            'indice_agent' => $indiceAgent,
            'salaire_global_annuel' => $salaireGlobalAnnuel,
            'lieu_service' => $personne['provenance'],
            'statut' => $statut,
            'montant_calcule' => $montantCalcule,
            'montant_approuve' => in_array($statut, ['valide', 'rembourse', 'cloture'], true) ? $montantCalcule : null,
            'valide_par' => in_array($statut, ['valide', 'rembourse', 'cloture'], true) ? self::DECLARE_PAR_ID : null,
            'valide_at' => in_array($statut, ['valide', 'rembourse', 'cloture'], true) ? $dateRetour : null,
            'rembourse_le' => in_array($statut, ['rembourse', 'cloture'], true) ? $dateRetour?->copy()->addDays(10) : null,
            'rembourse_par' => in_array($statut, ['rembourse', 'cloture'], true) ? self::DECLARE_PAR_ID : null,
            // Volet REGLEMENT DEFINITIF (verso) : uniquement pour les
            // fiches deja soldees — realiste, pas rempli avant.
            'reglement_indemnite_normale_nombre' => $estSolde ? $avanceIndemniteNombre : null,
            'reglement_indemnite_normale_taux' => $estSolde ? $avanceIndemniteTaux : null,
            'reglement_total' => $estSolde ? $montantCalcule : null,
            'reglement_montant_avances' => $estSolde ? $avanceTotal : null,
            'reglement_reste_a_payer' => $estSolde ? max(0, $montantCalcule - $avanceTotal) : null,
            'reglement_lieu' => $estSolde ? $centre->centre : null,
            'reglement_date' => $estSolde ? $dateRetour?->copy()->addDays(10) : null,
        ]);

        if ($chemins) {
            foreach (['Recto' => $chemins['recto'], 'Verso' => $chemins['verso']] as $cote => $chemin) {
                JustificatifFraisDeplacement::create([
                    'mission_id' => $fiche->id,
                    'nom_original' => 'feuille-deplacement-'.Str::slug($cote).'.pdf',
                    'chemin' => $chemin,
                    'mime_type' => 'application/pdf',
                    'taille' => Storage::disk('public')->size($chemin),
                    'depose_par' => self::DECLARE_PAR_ID,
                    'commentaire' => $cote,
                ]);
            }
        }
    }

    /**
     * Statut cohérent avec l'avancement de la convocation : une session
     * clôturée a des fiches déjà remboursées/clôturées, une session juste
     * envoyée en est encore au calcul/à la validation.
     */
    private function tirerStatut(string $statutConvocation): string
    {
        return match ($statutConvocation) {
            'cloturee' => random_int(1, 100) <= 70 ? 'cloture' : 'rembourse',
            'envoyee' => random_int(1, 100) <= 60 ? 'valide' : 'calcule',
            default => 'calcule',
        };
    }

    /**
     * Copie de FraisDeplacementController::determinerGroupe() (voir
     * commentaire de classe) — mêmes seuils, même logique.
     *
     * @return array{groupe: string, taux_base: int}
     */
    private function determinerGroupe(string $statutAgent, ?float $indice, ?float $montantMensuel): array
    {
        if ($statutAgent === 'fonctionnaire') {
            $indice ??= 0;

            $groupe = match (true) {
                $indice >= self::SEUIL_INDICE_GROUPE_I => 'I',
                $indice >= self::SEUIL_INDICE_GROUPE_II => 'II',
                default => 'III',
            };

            return ['groupe' => $groupe, 'taux_base' => self::TAUX_PAR_GROUPE[$groupe]];
        }

        $salaireAnnuel = ($montantMensuel ?? 0) * 12;

        $groupe = match (true) {
            $salaireAnnuel >= self::SEUIL_SALAIRE_ANNUEL_GROUPE_I => 'I',
            $salaireAnnuel >= self::SEUIL_SALAIRE_ANNUEL_GROUPE_II => 'II',
            default => 'III',
        };

        return ['groupe' => $groupe, 'taux_base' => self::TAUX_PAR_GROUPE[$groupe]];
    }

    /**
     * Recto/verso factices (disque "public", générés une seule fois et
     * réutilisés par toutes les fiches — même principe que
     * IndemnitesDemoSeeder::genererFichiersPlaceholder() pour les pièces
     * justificatives) : le bouton "Voir/Télécharger" des pièces jointes
     * fonctionne vraiment.
     *
     * @return array{recto: string, verso: string}
     */
    private function cheminsPlaceholder(): array
    {
        $chemins = [
            'recto' => 'missions-deplacement/demo/recto.pdf',
            'verso' => 'missions-deplacement/demo/verso.pdf',
        ];

        foreach (['recto' => 'Feuille de déplacement — Recto', 'verso' => 'Feuille de déplacement — Verso'] as $cle => $titre) {
            if (! Storage::disk('public')->exists($chemins[$cle])) {
                Storage::disk('public')->put($chemins[$cle], $this->construirePdfMinimal($titre));
            }
        }

        return $chemins;
    }

    private function construirePdfMinimal(string $titre): string
    {
        $texteEchappe = addcslashes('Document de demonstration - '.$titre, '()\\');
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
