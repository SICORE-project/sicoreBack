<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\AttachBeneficiairesConvocationRequest;
use App\Models\Convocations as ConvocationModel;

class ConvocationBeneficiaireController extends Controller
{
    use ApiResponseTrait;

    public function index(string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        return $this->success(
            'Bénéficiaires de la convocation.',
            $convocation->enseignants()->paginate(15)
        );
    }

    public function store(AttachBeneficiairesConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $beneficiaires = $request->validated('beneficiaires');

        if ($beneficiaires) {
            // Les centre_id valides doivent appartenir a CETTE convocation
            // (la regle 'exists:convocation_centres,id' ne verifie que
            // l'existence de la ligne, pas son rattachement).
            $centresValides = $convocation->centres()->pluck('id')->all();

            // Nouveau format : [ ['enseignant_id' => 1, 'fonction' => 'President de jury', 'centre_id' => 3], ... ]
            $sync = collect($beneficiaires)->mapWithKeys(function (array $b) use ($centresValides) {
                $centreId = $b['centre_id'] ?? null;

                if ($centreId && ! in_array($centreId, $centresValides, true)) {
                    $centreId = null;
                }

                return [
                    $b['enseignant_id'] => [
                        'fonction' => $b['fonction'] ?? null,
                        'centre_id' => $centreId,
                    ],
                ];
            })->all();
        } else {
            // Ancien format : liste brute d'IDs, sans fonction ni centre.
            $sync = collect($request->validated('enseignant_ids'))->mapWithKeys(fn ($enseignantId) => [
                $enseignantId => ['fonction' => null, 'centre_id' => null],
            ])->all();
        }

        $convocation->enseignants()->syncWithoutDetaching($sync);

        return $this->success(
            'Bénéficiaires ajoutés avec succès.',
            $convocation->enseignants()->get(),
            201
        );
    }
}