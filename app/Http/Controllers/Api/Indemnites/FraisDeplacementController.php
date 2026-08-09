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
use App\Models\MissionDeplacement;
use App\Models\LigneFraisDeplacement;
use App\Models\BaremeDeplacement;
use App\Models\JustificatifFraisDeplacement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FraisDeplacementController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = MissionDeplacement::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('beneficiaire_id')) {
            $query->where('beneficiaire_id', $request->query('beneficiaire_id'));
        }

        $missions = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des frais de déplacement.', $missions);
    }

    public function store(StoreFraisDeplacementRequest $request)
    {
        $data = $request->validated();
        $data['reference'] = 'MSN-' . Str::upper(Str::random(8));
        $data['declare_par'] = $request->user()?->id;
        $data['statut'] = 'brouillon';

        $mission = MissionDeplacement::create($data);

        return $this->success('Frais de déplacement créé avec succès.', $mission, 201);
    }

    public function show(string $id)
    {
        $mission = MissionDeplacement::with('lignes', 'justificatifs')->find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        return $this->success('Frais de déplacement trouvé.', $mission);
    }

    public function update(UpdateFraisDeplacementRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        if (in_array($mission->statut, ['valide', 'rembourse', 'cloture'], true)) {
            return $this->error('Impossible de modifier un dossier déjà traité.', 422);
        }

        $mission->update($request->validated());

        return $this->success('Frais de déplacement mis à jour avec succès.', $mission);
    }

    public function destroy(string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        $mission->delete();

        return $this->success('Frais de déplacement supprimé avec succès.');
    }

    /**
     * Calcule le montant des lignes de frais d'une mission à partir des barèmes.
     */
    public function calculer(CalculerFraisDeplacementRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        $montantTotal = DB::transaction(function () use ($request, $mission) {
            $total = 0;

            foreach ($request->validated('lignes') as $ligneData) {
                $bareme = isset($ligneData['bareme_id']) ? BaremeDeplacement::find($ligneData['bareme_id']) : null;
                $tauxUnitaire = $ligneData['taux_unitaire'] ?? $bareme?->taux_unitaire ?? 0;
                $montantCalcule = (float) $tauxUnitaire * (float) $ligneData['quantite'];

                if ($bareme && $bareme->plafond !== null && $montantCalcule > (float) $bareme->plafond) {
                    $montantCalcule = (float) $bareme->plafond;
                }

                LigneFraisDeplacement::create([
                    'mission_id' => $mission->id,
                    'type_frais' => $ligneData['type_frais'],
                    'bareme_id' => $ligneData['bareme_id'] ?? null,
                    'quantite' => $ligneData['quantite'],
                    'taux_unitaire' => $tauxUnitaire,
                    'montant_calcule' => $montantCalcule,
                    'plafond_applique' => $bareme->plafond ?? null,
                    'justificatif_obligatoire' => $bareme->justificatif_obligatoire ?? false,
                    'description' => $ligneData['description'] ?? null,
                ]);

                $total += $montantCalcule;
            }

            return $total;
        });

        $mission->update(['statut' => 'calcule', 'montant_calcule' => $montantTotal]);

        return $this->success('Frais de déplacement calculé avec succès.', $mission->fresh('lignes'));
    }

    public function justificatifs(string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        return $this->success('Justificatifs du frais de déplacement.', $mission->justificatifs);
    }

    public function deposerJustificatif(DeposerJustificatifFraisRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        $fichier = $request->file('fichier');
        $chemin = $fichier->store("frais-deplacement/{$id}", 'public');

        $justificatif = JustificatifFraisDeplacement::create([
            'mission_id' => $mission->id,
            'ligne_frais_id' => $request->validated('ligne_frais_id'),
            'nom_original' => $fichier->getClientOriginalName(),
            'chemin' => $chemin,
            'mime_type' => $fichier->getClientMimeType(),
            'taille' => $fichier->getSize(),
            'depose_par' => $request->user()?->id,
            'commentaire' => $request->validated('commentaire'),
        ]);

        return $this->success('Justificatif déposé avec succès.', $justificatif, 201);
    }

    public function supprimerJustificatif(string $id, string $justificatifId)
    {
        $justificatif = JustificatifFraisDeplacement::where('mission_id', $id)->find($justificatifId);

        if (! $justificatif) {
            return $this->error('Justificatif introuvable.', 404);
        }

        Storage::disk('public')->delete($justificatif->chemin);
        $justificatif->delete();

        return $this->success('Justificatif supprimé avec succès.');
    }

    public function valider(Request $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        $mission->update([
            'statut' => 'valide',
            'montant_approuve' => $mission->montant_calcule,
            'valide_par' => $request->user()?->id,
            'valide_at' => now(),
        ]);

        return $this->success('Frais de déplacement validé avec succès.', $mission);
    }

    public function rejeter(RejeterFraisDeplacementRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        $mission->update([
            'statut' => 'rejete',
            'motif_rejet' => $request->validated('motif_rejet'),
        ]);

        return $this->success('Frais de déplacement rejeté.', $mission);
    }

    public function rembourser(RembourserFraisDeplacementRequest $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        if ($mission->statut !== 'valide') {
            return $this->error('Seul un dossier validé peut être remboursé.', 422);
        }

        $mission->update([
            'statut' => 'rembourse',
            'montant_approuve' => $request->validated('montant_approuve') ?? $mission->montant_approuve,
            'rembourse_le' => now(),
            'rembourse_par' => $request->user()?->id,
        ]);

        return $this->success('Frais de déplacement remboursé avec succès.', $mission);
    }

    public function relancer(Request $request, string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        $mission->update([
            'relance_at' => now(),
            'notification_at' => now(),
            'notification_message' => $request->input('message', 'Relance concernant votre dossier de frais de déplacement.'),
        ]);

        return $this->success('Relance effectuée avec succès.', $mission);
    }

    public function cloturer(string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Frais de déplacement introuvable.', 404);
        }

        if ($mission->statut !== 'rembourse') {
            return $this->error('Seul un dossier remboursé peut être clôturé.', 422);
        }

        $mission->update(['statut' => 'cloture']);

        return $this->success('Frais de déplacement clôturé avec succès.', $mission);
    }
}
