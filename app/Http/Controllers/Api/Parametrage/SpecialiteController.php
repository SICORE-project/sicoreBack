<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\Specialite\StoreSpecialiteRequest;
use App\Http\Resources\Parametrage\SpecialiteResource;
use App\Services\Parametrage\SpecialiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SpecialiteController extends Controller
{
    public function __construct(
        protected SpecialiteService $specialiteService) {
    }

    public function store(StoreSpecialiteRequest $request): JsonResponse
    {
        $specialite = $this->specialiteService->create(
            $request->validated()
        );

        Log::info('Création spécialité', [
            'action' => 'CREATE_SPECIALITE',
            'user_id' => auth()->id(),
            'specialite_id' => $specialite->id,
            'code' => $specialite->code,
            'libelle' => $specialite->libelle,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spécialité créée avec succès.',
            'data' => new SpecialiteResource($specialite),
        ], 201);
    }
}