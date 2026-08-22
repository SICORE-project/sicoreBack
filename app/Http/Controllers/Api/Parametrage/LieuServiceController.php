<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreLieuServiceRequest;
use App\Models\Parametrage\LieuService;
use Illuminate\Http\JsonResponse;

class LieuServiceController extends Controller
{
    public function store(StoreLieuServiceRequest $request): JsonResponse
    {
        $lieuService = LieuService::create([
            ...$request->validated(),
            'type' => 'IEF',
            'perimetre' => 'regional',
            'est_actif' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lieu de service créé avec succès.',
            'data' => $lieuService->load([
                'ia:id,code,libelle',
                'ief:id,ia_id,code,libelle',
            ]),
        ], 201);
    }
}
