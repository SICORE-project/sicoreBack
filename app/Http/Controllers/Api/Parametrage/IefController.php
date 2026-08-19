<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\Ief\StoreIefRequest;
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
}