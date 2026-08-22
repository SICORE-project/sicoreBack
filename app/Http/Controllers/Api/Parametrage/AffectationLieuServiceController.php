<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\AffecterEnseignantLieuServiceRequest;
use App\Models\Parametrage\LieuService;
use App\Models\Personnel\AffectationEnseignant;
use App\Models\Personnel\Enseignant;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffectationLieuServiceController extends Controller
{
    public function store(
        AffecterEnseignantLieuServiceRequest $request,
        Enseignant $enseignant
    ): JsonResponse {
        $data = $request->validated();
        $userId = $request->user()->getKey();

        $affectation = DB::transaction(function () use ($data, $enseignant, $userId) {
            $enseignant = Enseignant::query()->lockForUpdate()->findOrFail($enseignant->getKey());
            $lieuService = LieuService::query()->where('est_actif', true)->findOrFail($data['lieu_service_id']);
            $ancienne = AffectationEnseignant::query()
                ->where('enseignant_id', $enseignant->getKey())
                ->where('est_active', true)
                ->lockForUpdate()
                ->latest('date_debut')
                ->first();

            if ($ancienne?->lieu_service_id === $lieuService->getKey()) {
                throw ValidationException::withMessages([
                    'lieu_service_id' => 'L’enseignant est déjà affecté à ce lieu de service.',
                ]);
            }

            $dateDebut = CarbonImmutable::parse($data['date_debut']);

            if ($ancienne) {
                $ancienneDateDebut = CarbonImmutable::parse($ancienne->date_debut);
                if ($dateDebut->lessThanOrEqualTo($ancienneDateDebut)) {
                    throw ValidationException::withMessages([
                        'date_debut' => 'La nouvelle affectation doit commencer après l’affectation active.',
                    ]);
                }

                $ancienne->update([
                    'date_fin' => $dateDebut->subDay()->toDateString(),
                    'est_active' => false,
                    'updated_by' => $userId,
                ]);
            }

            $affectation = AffectationEnseignant::create([
                ...$data,
                'enseignant_id' => $enseignant->getKey(),
                'ia_id' => $lieuService->ia_id,
                'ief_id' => $lieuService->ief_id,
                'type' => $data['type'] ?? ($ancienne ? 'reaffectation' : 'affectation'),
                'est_active' => true,
                'created_by' => $userId,
            ]);

            $enseignant->update([
                'lieu_service_id' => $lieuService->getKey(),
                'ia_id' => $lieuService->ia_id,
                'ief_id' => $lieuService->ief_id,
            ]);

            return $affectation;
        });

        return response()->json([
            'success' => true,
            'message' => 'Enseignant affecté au lieu de service avec succès.',
            'data' => $affectation->load(['enseignant', 'lieuService', 'ia', 'ief']),
        ], 201);
    }
}
