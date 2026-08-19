<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\Ief\StoreIefRequest;
use App\Http\Requests\Parametrage\Ief\UpdateIefRequest;
use App\Http\Requests\Parametrage\Ief\ChangeIefStatusRequest;
use App\Http\Resources\Parametrage\IefResource;
use App\Services\Parametrage\IefService;


use Illuminate\Support\Facades\Log;

class IefController extends Controller
{
    public function __construct(
        protected IefService $iefService
    ) {}

    /**
     * Liste des IEF.
     */
    public function index()
    {
        $iefs = $this->iefService->getAll(
            request()->only([
                'search',
                'ia_id',
                'est_actif',
                'sort_by',
                'sort_direction',
                'per_page',
            ])
        );

        return IefResource::collection($iefs)
            ->additional([
                'success' => true,
            ]);
    }

    /**
     * Détail d'une IEF.
     */
    public function show(int $id)
{
    $user = request()->user();

    $ief = $this->iefService->findById($id);


    if (
        $user->hasRole('gestionnaire_ia') &&
        (int) $user->ia_id !== (int) $ief->ia_id
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Vous n’êtes pas autorisé à consulter cette IEF.',
        ], 403);
    }

    return response()->json([
        'success' => true,
        'data' => new IefResource($ief),
    ]);
}

    /**
     * Création d'une IEF.
     */
    public function store(StoreIefRequest $request)
{
    $user = $request->user();

    if (
        !$user->hasRole('super_admin') &&
        !$user->hasRole('admin')
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Vous n’êtes pas autorisé à créer une IEF.',
        ], 403);
    }

    try {
        $ief = $this->iefService->create(
            $request->validated()
        );
    } catch (\DomainException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }

    Log::info('Création IEF', [
        'action' => 'CREATE_IEF',
        'user_id' => $user->id,
        'ief_id' => $ief->id,
        'ia_id' => $ief->ia_id,
        'code' => $ief->code,
        'libelle' => $ief->libelle,
        'ip' => $request->ip(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'IEF créée avec succès.',
        'data' => new IefResource($ief),
    ], 201);
}

public function byIa(int $id)
{
    $user = request()->user();

    $ia = \App\Models\Parametrage\Ia::find($id);

    if (!$ia) {
        return response()->json([
            'success' => false,
            'message' => 'L’Inspection d’Académie demandée n’existe pas.',
        ], 404);
    }

    if (
        $user->hasRole('gestionnaire_ia') &&
        (int) $user->ia_id !== (int) $ia->id
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Vous n’êtes pas autorisé à consulter les IEF de cette IA.',
        ], 403);
    }

    $iefs = $this->iefService->getByIa(
        $ia->id,
        request()->only([
            'search',
            'est_actif',
            'sort_by',
            'sort_direction',
            'per_page',
        ])
    );

    return response()->json([
        'success' => true,
        'ia' => [
            'id' => $ia->id,
            'code' => $ia->code,
            'libelle' => $ia->libelle,
        ],
        'data' => $iefs,
    ]);
}

public function update(UpdateIefRequest $request, int $id)
{
    $user = $request->user();

    /*
    |--------------------------------------------------------------------------
    | Vérification des rôles autorisés
    |--------------------------------------------------------------------------
    */

    if (
        !$user->hasRole('super_admin') &&
        !$user->hasRole('admin')
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Vous n’êtes pas autorisé à modifier une IEF.',
        ], 403);
    }

    try {
        // Valeurs avant modification
        $iefAvant = $this->iefService->findById($id);

        $anciennesValeurs = $iefAvant->only([
            'code',
            'libelle',
            'ia_id',
            'adresse',
            'telephone',
            'email',
            'responsable',
            'est_actif',
        ]);

        // Modification
        $ief = $this->iefService->update(
            $id,
            $request->validated()
        );

        $nouvellesValeurs = $ief->only([
            'code',
            'libelle',
            'ia_id',
            'adresse',
            'telephone',
            'email',
            'responsable',
            'est_actif',
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'L’IEF demandée n’existe pas.',
        ], 404);

    } catch (\DomainException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | Journalisation
    |--------------------------------------------------------------------------
    */

    Log::info('Modification IEF', [
        'action' => 'UPDATE_IEF',
        'user_id' => $user->id,
        'ief_id' => $ief->id,
        'anciennes_valeurs' => $anciennesValeurs,
        'nouvelles_valeurs' => $nouvellesValeurs,
        'ip' => $request->ip(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'IEF mise à jour avec succès.',
        'data' => new IefResource($ief),
    ]);
}

public function changeStatus(ChangeIefStatusRequest $request, int $id)
{
    $user = $request->user();

    /*
    | Autorisation
    */

    if (
        !$user->hasRole('super_admin') &&
        !$user->hasRole('admin')
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Vous n’êtes pas autorisé à modifier le statut d’une IEF.',
        ], 403);
    }

    try {
        $iefAvant = $this->iefService->findById($id);

        $ancienStatut = $iefAvant->est_actif;

        $ief = $this->iefService->changeStatus(
            $id,
            $request->boolean('est_actif')
        );

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'L’IEF demandée n’existe pas.',
        ], 404);

    } catch (\DomainException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }

    Log::info('Changement statut IEF', [
        'action' => $ief->est_actif
            ? 'ACTIVATE_IEF'
            : 'DEACTIVATE_IEF',

        'user_id' => $user->id,
        'ief_id' => $ief->id,
        'ia_id' => $ief->ia_id,
        'ancien_statut' => $ancienStatut,
        'nouveau_statut' => $ief->est_actif,
        'ip' => $request->ip(),
    ]);

    return response()->json([
        'success' => true,
        'message' => $ief->est_actif
            ? 'IEF activée avec succès.'
            : 'IEF désactivée avec succès.',
        'data' => new IefResource($ief),
    ]);
}
}