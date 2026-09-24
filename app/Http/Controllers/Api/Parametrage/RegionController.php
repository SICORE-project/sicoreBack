<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\Region\StoreRegionRequest;
use App\Http\Requests\Parametrage\Region\UpdateRegionRequest;
use App\Http\Resources\Parametrage\RegionResource;
use App\Services\Parametrage\RegionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DomainException;

class RegionController extends Controller
{
    public function __construct(
        protected RegionService $regionService
    ) {}

    /**
     * Liste des régions.
     */
    public function index(Request $request)
    {
        $regions = $this->regionService->getAll(
            $request->only([
                'search',
                'est_actif',
                'sort_by',
                'sort_direction',
                'per_page',
            ])
        );

        return RegionResource::collection($regions)
            ->additional([
                'success' => true,
            ]);
    }

    /**
     * Détail d'une région.
     */
    public function show(int $id)
    {
        $region = $this->regionService->findById($id);

        return response()->json([
            'success' => true,
            'data' => new RegionResource($region),
        ]);
    }

    /**
     * Création d'une région.
     */
    public function store(StoreRegionRequest $request)
    {
        $region = $this->regionService->create(
            $request->validated()
        );

        Log::info('Création région', [
            'action' => 'CREATE_REGION',
            'user_id' => $request->user()?->id,
            'region_id' => $region->id,
            'code' => $region->code,
            'libelle' => $region->libelle,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Région créée avec succès.',
            'data' => new RegionResource($region),
        ], 201);
    }

    /**
     * Modification d'une région.
     */
    public function update(UpdateRegionRequest $request, int $id)
    {
        $regionAvant = $this->regionService->findById($id);

        $anciennesValeurs = $regionAvant->only([
            'code',
            'libelle',
            'chef_lieu',
            'superficie',
            'population',
            'est_actif',
        ]);

        $region = $this->regionService->update(
            $id,
            $request->validated()
        );

        $nouvellesValeurs = $region->only([
            'code',
            'libelle',
            'chef_lieu',
            'superficie',
            'population',
            'est_actif',
        ]);

        Log::info('Modification région', [
            'action' => 'UPDATE_REGION',
            'user_id' => $request->user()?->id,
            'region_id' => $region->id,
            'anciennes_valeurs' => $anciennesValeurs,
            'nouvelles_valeurs' => $nouvellesValeurs,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Région mise à jour avec succès.',
            'data' => new RegionResource($region),
        ]);
    }

    /**
     * Activation / désactivation d'une région.
     */
    public function changeStatut(Request $request, int $id)
    {
        $request->validate(
            [
                'est_actif' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'est_actif.required' => 'Le statut de la région est obligatoire.',
                'est_actif.boolean' => 'Le statut de la région doit être vrai ou faux.',
            ]
        );

        $regionAvant = $this->regionService->findById($id);

        $ancienStatut = (bool) $regionAvant->est_actif;

        $region = $this->regionService->changeStatut(
            $id,
            (bool) $request->boolean('est_actif')
        );

        Log::info('Changement statut région', [
            'action' => $region->est_actif
                ? 'ACTIVATE_REGION'
                : 'DEACTIVATE_REGION',
            'user_id' => $request->user()?->id,
            'region_id' => $region->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => (bool) $region->est_actif,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $region->est_actif
                ? 'Région activée avec succès.'
                : 'Région désactivée avec succès.',
            'data' => new RegionResource($region),
        ]);
    }

    /**
     * Suppression d'une région.
     */
    public function destroy(Request $request, int $id)
    {
        $region = $this->regionService->findById($id);

        $donneesAvantSuppression = $region->only([
            'id',
            'code',
            'libelle',
            'chef_lieu',
            'superficie',
            'population',
            'est_actif',
        ]);

        try {
            $this->regionService->delete($id);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        Log::warning('Suppression région', [
            'action' => 'DELETE_REGION',
            'user_id' => $request->user()?->id,
            'region_id' => $id,
            'donnees' => $donneesAvantSuppression,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Région supprimée avec succès.',
        ]);
    }
}