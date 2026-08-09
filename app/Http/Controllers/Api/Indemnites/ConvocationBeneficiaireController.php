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

        $convocation->enseignants()->syncWithoutDetaching($request->validated('enseignant_ids'));

        return $this->success(
            'Bénéficiaires ajoutés avec succès.',
            $convocation->enseignants()->get(),
            201
        );
    }
}
