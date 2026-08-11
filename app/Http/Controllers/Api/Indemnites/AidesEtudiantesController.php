<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreAideEtudianteRequest;
use App\Http\Requests\Indemnites\UpdateAideEtudianteRequest;
use App\Http\Requests\Indemnites\RejeterAideEtudianteRequest;
use App\Http\Requests\Indemnites\DeposerPieceAideRequest;
use App\Models\DemandeAide;
use App\Models\TypeAide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AidesEtudiantesController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = DemandeAide::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('etudiant_id')) {
            $query->where('etudiant_id', $request->query('etudiant_id'));
        }

        $aides = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des demandes d\'aides étudiantes.', $aides);
    }

    public function store(StoreAideEtudianteRequest $request)
    {
        $data = $request->validated();
        $data['reference'] = 'AIDE-' . Str::upper(Str::random(8));
        $data['utilisateur_id'] = $data['utilisateur_id'] ?? $request->user()?->id;
        $data['statut'] = 'en_attente';

        $demande = DemandeAide::create($data);

        return $this->success('Demande d\'aide créée avec succès.', $demande, 201);
    }

    public function show(string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        return $this->success('Demande d\'aide trouvée.', $demande);
    }

    public function update(UpdateAideEtudianteRequest $request, string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        $demande->update($request->validated());

        return $this->success('Demande d\'aide mise à jour avec succès.', $demande);
    }

    public function destroy(string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        $demande->delete();

        return $this->success('Demande d\'aide supprimée avec succès.');
    }

    /**
     * Valide et attribue l'aide (voir même remarque que BoursesController::valider()).
     */
    public function valider(Request $request, string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        $demande->update([
            'statut' => 'valide',
            'traite_par' => $request->user()?->id,
            'traite_at' => now(),
        ]);

        return $this->success('Aide validée et attribuée avec succès.', $demande);
    }

    public function rejeter(RejeterAideEtudianteRequest $request, string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        $demande->update([
            'statut' => 'rejete',
            'motif_rejet' => $request->validated('motif_rejet'),
            'traite_par' => $request->user()?->id,
            'traite_at' => now(),
        ]);

        return $this->success('Demande d\'aide rejetée.', $demande);
    }

    public function calculerMontant(string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        $type = TypeAide::find($demande->type_aide_id);
        $montant = $type->montant_defaut ?? 0;

        $demande->update(['montant_attribue' => $montant]);

        return $this->success('Montant de l\'aide calculé avec succès.', [
            'demande_id' => $demande->id,
            'montant_attribue' => $montant,
            'periodicite' => $type->periodicite ?? null,
        ]);
    }

    /**
     * Voir la note de BoursesController::pieces() : même limitation de schéma,
     * les fichiers sont stockés sous `aides-etudiantes/{id}/`.
     */
    public function pieces(string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        $fichiers = collect(Storage::disk('public')->files("aides-etudiantes/{$id}"))
            ->map(fn ($chemin) => [
                'chemin' => $chemin,
                'url' => Storage::disk('public')->url($chemin),
            ])->values();

        return $this->success('Pièces de la demande d\'aide.', $fichiers);
    }

    public function deposerPiece(DeposerPieceAideRequest $request, string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        $fichier = $request->file('document');
        $chemin = $fichier->store("aides-etudiantes/{$id}", 'public');

        return $this->success('Pièce déposée avec succès.', [
            'type' => $request->validated('type'),
            'chemin' => $chemin,
            'url' => Storage::disk('public')->url($chemin),
        ], 201);
    }

    public function archiver(string $id)
    {
        $demande = DemandeAide::find($id);

        if (! $demande) {
            return $this->error('Demande d\'aide introuvable.', 404);
        }

        $demande->update(['statut' => 'archive']);

        return $this->success('Demande d\'aide archivée avec succès.', $demande);
    }
}
