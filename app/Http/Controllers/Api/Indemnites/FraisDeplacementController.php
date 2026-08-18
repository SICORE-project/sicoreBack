<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreFraisDeplacementRequest;
use App\Http\Requests\Indemnites\UpdateFraisDeplacementRequest;
use App\Http\Requests\Indemnites\CalculerFraisDeplacementRequest;
use App\Http\Requests\Indemnites\DeposerJustificatifFraisRequest;
use App\Http\Requests\Indemnites\RejeterFraisDeplacementRequest;
use App\Http\Requests\Indemnites\RembourserFraisDeplacementRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\MissionDeplacement;
use App\Models\Indemnite\LigneFraisDeplacement;
use App\Models\Indemnite\BaremeDeplacement;
use App\Models\Indemnite\JustificatifFraisDeplacement;
use App\Models\Indemnite\piece_justificatives;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * "Fiche de déplacement" : formulaire calqué sur la feuille de
 * déplacement papier (Ministère des Finances et du Budget) — délivré à UN
 * bénéficiaire convoqué (enseignants), pour UNE convocation dont le
 * dossier de pièces justificatives est complet (mêmes 6 documents que la
 * page Pièces justificatives — voir beneficiairesEligibles()).
 *
 * Montant : vacataire = 150 000 F fixe, contractuel = montant saisi
 * librement, fonctionnaire = en attente de l'étape "Calcul" (barème par
 * indice, pas encore spécifié) — voir store()/calculer().
 */
class FraisDeplacementController extends Controller
{
    use ApiResponseTrait;

    /**
     * Montant fixe pour un bénéficiaire vacataire — cf. demande
     * utilisatrice ("champ Somme avec un montant fixe à 150 000").
     */
    private const MONTANT_VACATAIRE = 150000;

    public function index(Request $request)
    {
        $query = MissionDeplacement::with(['beneficiaire', 'convocation']);

        if ($request->filled('convocation_id')) {
            $query->where('convocation_id', $request->query('convocation_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('beneficiaire_id')) {
            $query->where('beneficiaire_id', $request->query('beneficiaire_id'));
        }

        $missions = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des fiches de déplacement.', $missions);
    }

    /**
     * Bénéficiaires d'UNE convocation dont le dossier de pièces
     * justificatives est complet (mêmes 6 types que la page Pièces
     * justificatives — service_fait, ordre_mission, rapport_mission,
     * bulletin_salaire, accuse_reception, dossier_convocation) — ce sont
     * les seuls pour qui une fiche de déplacement peut être créée. Indique
     * aussi si une fiche existe déjà pour chacun (évite les doublons côté
     * front).
     */
    public function beneficiairesEligibles(Request $request)
    {
        $convocationId = $request->query('convocation_id');

        if (! $convocationId) {
            return $this->error('convocation_id est requis.', 422);
        }

        // "centres.chefCentre"/"centres.presidentJury" : le chef de centre
        // et le président du jury de chaque centre déposent eux aussi leurs
        // pièces justificatives et doivent donc pouvoir recevoir une fiche
        // de déplacement — voir le même principe déjà en place sur la page
        // Pièces justificatives (PiecesJustificativesController::
        // construireMembres() côté front, et le commentaire sur
        // ConvocationsController::index() côté back). Sans ça, un chef de
        // centre/président du jury au dossier complet n'apparaissait
        // jamais ici : impossible d'établir sa fiche quels que soient ses
        // documents déposés.
        $convocation = ConvocationModel::with(['enseignants', 'centres.chefCentre', 'centres.presidentJury'])->find($convocationId);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $membres = $convocation->enseignants->keyBy('id');

        foreach ($convocation->centres as $centre) {
            if ($centre->chefCentre) {
                $membres->put($centre->chefCentre->id, $centre->chefCentre);
            }

            if ($centre->presidentJury) {
                $membres->put($centre->presidentJury->id, $centre->presidentJury);
            }
        }

        $typesRequis = array_keys(piece_justificatives::TYPES);

        // Types présents (hors pièces rejetées) par bénéficiaire, pour cette
        // convocation — un dossier est "complet" quand les 6 types requis y
        // figurent tous.
        $typesParEnseignant = piece_justificatives::where('convocation_id', $convocationId)
            ->where('statut', '!=', 'rejete')
            ->get(['enseignant_id', 'type'])
            ->groupBy('enseignant_id')
            ->map(fn ($pieces) => $pieces->pluck('type')->unique()->all());

        // ->get(['id','beneficiaire_id','statut'])->keyBy() plutot que
        // pluck('id', ...) : la page front "Frais de déplacement" (liste
        // reorganisee comme "Pieces justificatives") a besoin du statut de
        // la fiche existante pour ses cartes de stats (fiches en attente/
        // rejetees), pas seulement de son id.
        $missionsExistantes = MissionDeplacement::where('convocation_id', $convocationId)
            ->get(['id', 'beneficiaire_id', 'statut'])
            ->keyBy('beneficiaire_id');

        $beneficiaires = $membres->map(function ($enseignant) use ($typesRequis, $typesParEnseignant, $missionsExistantes) {
            $typesPresents = $typesParEnseignant->get($enseignant->id, []);
            $complet = count(array_intersect($typesRequis, $typesPresents)) === count($typesRequis);
            $mission = $missionsExistantes->get($enseignant->id);

            return [
                'id' => $enseignant->id,
                'nom' => $enseignant->nom,
                'prenom' => $enseignant->prenom,
                'matricule' => $enseignant->matricule,
                'categorie_personnel' => $enseignant->categorie_personnel,
                'indice' => $enseignant->indice,
                'dossier_complet' => $complet,
                'pieces_manquantes' => $complet ? [] : array_values(array_diff($typesRequis, $typesPresents)),
                'fiche_deplacement_id' => $mission?->id,
                'fiche_statut' => $mission?->statut,
            ];
        })->values();

        return $this->success('Bénéficiaires de la convocation.', [
            'complets' => $beneficiaires->where('dossier_complet', true)->values(),
            'incomplets' => $beneficiaires->where('dossier_complet', false)->values(),
        ]);
    }

    public function store(StoreFraisDeplacementRequest $request)
    {
        $convocation = ConvocationModel::find($request->validated('convocation_id'));

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $enseignant = \App\Models\Parametrage\Enseignant::find($request->validated('beneficiaire_id'));

        if (! $enseignant) {
            return $this->error('Bénéficiaire introuvable.', 404);
        }

        if (! $this->dossierComplet((int) $convocation->id, (int) $enseignant->id)) {
            return $this->error("Le dossier de pièces justificatives de ce bénéficiaire n'est pas complet pour cette convocation.", 422);
        }

        // Type TOUJOURS re-dérivé depuis la fiche de l'enseignant (source de
        // vérité), jamais depuis ce que le front a soumis — cf. demande
        // utilisatrice ("qu'on doit récupérer puisqu'on sait déjà c'est
        // quoi"). Indice : idem s'il est déjà connu ; sinon on accepte la
        // saisie du formulaire ET on la mémorise sur l'enseignant, pour ne
        // plus avoir à la ressaisir la prochaine fois.
        $statutAgent = $enseignant->categorie_personnel ?? $request->validated('statut_agent');
        $indiceAgent = null;

        if ($statutAgent === 'fonctionnaire') {
            $indiceAgent = $enseignant->indice ?? $request->validated('indice_agent');

            if ($indiceAgent !== null && $enseignant->indice === null) {
                $enseignant->update(['indice' => $indiceAgent]);
            }
        }

        $montantCalcule = match ($statutAgent) {
            'vacataire' => self::MONTANT_VACATAIRE,
            'contractuel' => $request->validated('montant_saisi'),
            default => null, // fonctionnaire : attend l'étape "Calcul" (barème par indice, pas encore défini)
        };

        // Total du tableau "Décompte des avances au départ" (Nombre x Taux
        // par ligne, comme sur la feuille papier) — recalculé côté serveur,
        // jamais fait confiance au total envoyé par le front.
        $lignesAvance = [
            [$request->validated('avance_frais_transport_nombre'), $request->validated('avance_frais_transport_taux')],
            [$request->validated('avance_indemnite_normale_nombre'), $request->validated('avance_indemnite_normale_taux')],
            [$request->validated('avance_indemnite_reduite_nombre'), $request->validated('avance_indemnite_reduite_taux')],
            [$request->validated('avance_indemnite_partielle_nombre'), $request->validated('avance_indemnite_partielle_taux')],
        ];
        $avanceTotal = array_sum(array_map(
            fn ($ligne) => ($ligne[0] ?? 0) * ($ligne[1] ?? 0),
            $lignesAvance
        ));

        $mission = MissionDeplacement::create([
            'reference' => $this->genererReference(),
            'convocation_id' => $convocation->id,
            'beneficiaire_id' => $enseignant->id,
            'declare_par' => $request->user()?->id,
            'grade_emploi' => $request->validated('grade_emploi'),
            'lieu_depart' => $request->validated('lieu_depart'),
            'heure_depart' => $request->validated('heure_depart'),
            'lieu_destination' => $request->validated('lieu_destination'),
            'motif' => $request->validated('motif'),
            'date_depart' => $request->validated('date_depart'),
            'date_retour' => $request->validated('date_retour'),
            'distance_km' => $request->validated('distance_km'),
            'moyen_transport' => $request->validated('moyen_transport'),
            'ordre_service_numero' => $request->validated('ordre_service_numero'),
            'ordre_service_date' => $request->validated('ordre_service_date'),
            'ordre_service_emetteur' => $request->validated('ordre_service_emetteur'),
            'accompagne_de' => $request->validated('accompagne_de'),
            'groupe' => $request->validated('groupe'),
            'itineraire' => $request->validated('itineraire'),
            'poids_bagages_kg' => $request->validated('poids_bagages_kg'),
            'delivre_par' => $request->validated('delivre_par'),
            'date_emission_fiche' => $request->validated('date_emission_fiche') ?? now()->toDateString(),
            'avance_frais_transport_nombre' => $request->validated('avance_frais_transport_nombre'),
            'avance_frais_transport_taux' => $request->validated('avance_frais_transport_taux'),
            'avance_indemnite_normale_nombre' => $request->validated('avance_indemnite_normale_nombre'),
            'avance_indemnite_normale_taux' => $request->validated('avance_indemnite_normale_taux'),
            'avance_indemnite_reduite_nombre' => $request->validated('avance_indemnite_reduite_nombre'),
            'avance_indemnite_reduite_taux' => $request->validated('avance_indemnite_reduite_taux'),
            'avance_indemnite_partielle_nombre' => $request->validated('avance_indemnite_partielle_nombre'),
            'avance_indemnite_partielle_taux' => $request->validated('avance_indemnite_partielle_taux'),
            'avance_total' => $avanceTotal > 0 ? $avanceTotal : null,
            'arrete_somme' => $request->validated('arrete_somme'),
            'avance_versee' => $request->validated('avance_versee'),
            'date_fait_avance' => $request->validated('date_fait_avance'),
            'statut_agent' => $statutAgent,
            'indice_agent' => $indiceAgent,
            'salaire_global_annuel' => $request->validated('salaire_global_annuel'),
            'lieu_service' => $request->validated('lieu_service'),
            // vacataire/contractuel : montant déjà connu, la fiche est
            // directement "calculée". fonctionnaire : reste en brouillon en
            // attendant l'étape Calcul.
            'statut' => $montantCalcule !== null ? 'calcule' : 'brouillon',
            'montant_calcule' => $montantCalcule,
        ]);

        if ($request->hasFile('fichier')) {
            $this->enregistrerJustificatif($mission, $request->file('fichier'), $request->user()?->id);
        }

        return $this->success('Fiche de déplacement créée avec succès.', $mission->load('justificatifs'), 201);
    }

    public function show(string $id)
    {
        $mission = MissionDeplacement::with(['beneficiaire', 'convocation', 'lignes', 'justificatifs'])->find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        return $this->success('Fiche de déplacement trouvée.', $mission);
    }

    public function update(UpdateFraisDeplacementRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $mission->update($request->validated());

        return $this->success('Fiche de déplacement mise à jour avec succès.', $mission);
    }

    public function destroy(string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        foreach ($mission->justificatifs as $justificatif) {
            if ($justificatif->chemin) {
                Storage::disk('public')->delete($justificatif->chemin);
            }
        }

        $mission->delete();

        return $this->success('Fiche de déplacement supprimée avec succès.');
    }

    /**
     * Étape "Calcul" (multi-lignes, barèmes) — distincte du montant simple
     * déjà posé par store() pour vacataire/contractuel. Remplace les
     * lignes existantes de la mission par celles soumises.
     */
    public function calculer(CalculerFraisDeplacementRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $total = 0;

        DB::transaction(function () use ($request, $mission, &$total) {
            $mission->lignes()->delete();

            foreach ($request->validated('lignes') as $ligne) {
                $bareme = ! empty($ligne['bareme_id']) ? BaremeDeplacement::find($ligne['bareme_id']) : null;
                $taux = $ligne['taux_unitaire'] ?? $bareme?->taux_unitaire ?? 0;
                $montant = $ligne['quantite'] * $taux;

                if ($bareme?->plafond !== null && $montant > $bareme->plafond) {
                    $montant = $bareme->plafond;
                }

                $mission->lignes()->create([
                    'bareme_id' => $bareme?->id,
                    'type_frais' => $ligne['type_frais'],
                    'quantite' => $ligne['quantite'],
                    'taux_unitaire' => $taux,
                    'montant_calcule' => $montant,
                    'plafond_applique' => $bareme?->plafond,
                    'justificatif_obligatoire' => $bareme?->justificatif_obligatoire ?? false,
                    'description' => $ligne['description'] ?? null,
                ]);

                $total += $montant;
            }

            $mission->update(['montant_calcule' => $total, 'statut' => 'calcule']);
        });

        return $this->success('Frais calculés avec succès.', $mission->load('lignes'));
    }

    public function justificatifs(string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        return $this->success('Pièces jointes de la fiche de déplacement.', $mission->justificatifs()->latest()->get());
    }

    public function deposerJustificatif(DeposerJustificatifFraisRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $justificatif = $this->enregistrerJustificatif(
            $mission,
            $request->file('fichier'),
            $request->user()?->id,
            $request->validated('ligne_frais_id'),
            $request->validated('commentaire')
        );

        return $this->success('Pièce jointe déposée avec succès.', $justificatif, 201);
    }

    public function supprimerJustificatif(string $id, string $justificatifId)
    {
        $justificatif = JustificatifFraisDeplacement::where('mission_id', $id)->where('id', $justificatifId)->first();

        if (! $justificatif) {
            return $this->error('Pièce jointe introuvable.', 404);
        }

        if ($justificatif->chemin) {
            Storage::disk('public')->delete($justificatif->chemin);
        }

        $justificatif->delete();

        return $this->success('Pièce jointe supprimée avec succès.');
    }

    public function valider(Request $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $mission->update([
            'statut' => 'valide',
            'montant_approuve' => $mission->montant_approuve ?? $mission->montant_calcule,
            'valide_par' => $request->user()?->id,
            'valide_at' => now(),
        ]);

        return $this->success('Fiche de déplacement validée avec succès.', $mission);
    }

    public function rejeter(RejeterFraisDeplacementRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $mission->update([
            'statut' => 'rejete',
            'motif_rejet' => $request->validated('motif_rejet'),
        ]);

        return $this->success('Fiche de déplacement rejetée.', $mission);
    }

    public function rembourser(RembourserFraisDeplacementRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $mission->update([
            'statut' => 'rembourse',
            'montant_approuve' => $request->validated('montant_approuve') ?? $mission->montant_approuve ?? $mission->montant_calcule,
            'rembourse_par' => $request->user()?->id,
            'rembourse_le' => now(),
        ]);

        return $this->success('Fiche de déplacement marquée comme remboursée.', $mission);
    }

    public function relancer(Request $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $mission->update([
            'relance_at' => now(),
            'notification_at' => now(),
            'notification_message' => $request->input('message', 'Relance concernant votre fiche de déplacement.'),
        ]);

        return $this->success('Relance enregistrée avec succès.', $mission);
    }

    public function cloturer(Request $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $mission->update(['statut' => 'cloture']);

        return $this->success('Fiche de déplacement clôturée avec succès.', $mission);
    }

    /**
     * Un dossier est complet quand les 6 types de pièces justificatives
     * requis (piece_justificatives::TYPES) sont tous présents et non
     * rejetés pour ce couple convocation/enseignant — même règle que
     * beneficiairesEligibles(), revérifiée ici côté serveur avant création
     * (ne pas se fier uniquement à ce que le front affichait).
     */
    private function dossierComplet(int $convocationId, int $enseignantId): bool
    {
        $typesPresents = piece_justificatives::where('convocation_id', $convocationId)
            ->where('enseignant_id', $enseignantId)
            ->where('statut', '!=', 'rejete')
            ->pluck('type')
            ->unique();

        return count(array_intersect(array_keys(piece_justificatives::TYPES), $typesPresents->all())) === count(piece_justificatives::TYPES);
    }

    private function genererReference(): string
    {
        do {
            $reference = 'FD-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        } while (MissionDeplacement::where('reference', $reference)->exists());

        return $reference;
    }

    private function enregistrerJustificatif(
        MissionDeplacement $mission,
        \Illuminate\Http\UploadedFile $fichier,
        ?int $deposeParId,
        ?int $ligneFraisId = null,
        ?string $commentaire = null
    ): JustificatifFraisDeplacement {
        return JustificatifFraisDeplacement::create([
            'mission_id' => $mission->id,
            'ligne_frais_id' => $ligneFraisId,
            'nom_original' => $fichier->getClientOriginalName(),
            'chemin' => $fichier->store('frais-deplacement', 'public'),
            'mime_type' => $fichier->getClientMimeType(),
            'taille' => $fichier->getSize(),
            'depose_par' => $deposeParId,
            'commentaire' => $commentaire,
        ]);
    }
}
