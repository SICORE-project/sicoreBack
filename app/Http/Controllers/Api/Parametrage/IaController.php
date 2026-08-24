<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\Ia\StoreIaRequest;
use App\Http\Requests\Parametrage\Ia\UpdateIaRequest;
use App\Http\Resources\Parametrage\IaResource;
use App\Models\Parametrage\Region;
use App\Services\Parametrage\IaService;
use Illuminate\Support\Facades\Log;

class IaController extends Controller
{
    public function __construct(
        protected IaService $iaService
    ) {}

    /**
     * Liste des IA.
     */
   public function index()
{
    $ias = $this->iaService->getAll(
        request()->only([
            'search',
            'region_id',
            'sort_by',
            'sort_direction',
            'per_page',
        ])
    );

    return IaResource::collection($ias)
        ->additional([
            'success' => true,
        ]);
}
    /**
     * Détail d'une IA.
     */
    public function show(int $id)
    {
        $ia = $this->iaService->findById($id);

        return response()->json([
            'success' => true,
            'data' => new IaResource($ia),
        ]);
    }

    public function regionOptions()
    {
        return response()->json([
            'success' => true,
            'data' => Region::query()->actif()->orderBy('nom')->get(['id', 'code', 'nom']),
        ]);
    }

    /**
     * Création d'une IA.
     */
    public function store(StoreIaRequest $request)
    {
        $ia = $this->iaService->create(
            $request->validated()
        );

        // Journalisation de la création
        Log::info('Création IA', [
            'action' => 'CREATE_IA',
            'user_id' => $request->user()?->id,
            'ia_id' => $ia->id,
            'code' => $ia->code,
            'libelle' => $ia->libelle,
            'region_id' => $ia->region_id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'IA créée avec succès.',
            'data' => new IaResource($ia),
        ], 201);
    }

    /**
     * Modification d'une IA.
     */
    public function update(UpdateIaRequest $request, int $id)
    {
        // Récupérer les valeurs avant modification
        $iaAvant = $this->iaService->findById($id);

        $anciennesValeurs = $iaAvant->only([
            'code',
            'libelle',
            'region_id',
        ]);

        // Modification
        $ia = $this->iaService->update(
            $id,
            $request->validated()
        );

        $nouvellesValeurs = $ia->only([
            'code',
            'libelle',
            'region_id',
        ]);

        // Journalisation de la modification
        Log::info('Modification IA', [
            'action' => 'UPDATE_IA',
            'user_id' => $request->user()?->id,
            'ia_id' => $ia->id,
            'anciennes_valeurs' => $anciennesValeurs,
            'nouvelles_valeurs' => $nouvellesValeurs,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'IA mise à jour avec succès.',
            'data' => new IaResource($ia),
        ]);
    }

    /**
     * Suppression logique d'une IA.
     * Seul le Super Admin peut supprimer.
     */
    public function destroy(int $id)
    {
        $user = request()->user();

        if (!$user || !$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n’êtes pas autorisé à supprimer une IA.',
            ], 403);
        }

        // Récupérer les données avant suppression
        $ia = $this->iaService->findById($id);

        $donneesAvantSuppression = $ia->only([
            'id',
            'code',
            'libelle',
            'region_id',
        ]);

        // Soft delete
        $this->iaService->delete($id);

        // Journalisation de la suppression
        Log::warning('Suppression IA', [
            'action' => 'DELETE_IA',
            'user_id' => $user->id,
            'ia_id' => $id,
            'donnees' => $donneesAvantSuppression,
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'IA supprimée avec succès.',
        ]);
    }

}
