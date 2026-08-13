<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationCentresRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;

class ConvocationCentreController extends Controller
{
    use ApiResponseTrait;

    public function index(string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        return $this->success(
            'Centres de la convocation.',
            $convocation->centres()->with(['chefCentre', 'presidentJury', 'metiers'])->get()
        );
    }

    /**
     * Cree un ou plusieurs centres d'examen pour une convocation.
     *
     * Renvoie les centres crees DANS L'ORDRE d'envoi, pour que l'appelant
     * (le wizard front) puisse faire correspondre chaque centre soumis
     * (identifie par sa position dans le tableau) a son id reel en base,
     * et l'utiliser ensuite pour rattacher les membres du jury au bon
     * centre (voir ConvocationBeneficiaireController).
     */
    public function store(StoreConvocationCentresRequest $request, string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $centres = collect($request->validated('centres'))->map(
            fn (array $donnees) => $convocation->centres()->create($donnees)
        );

        return $this->success('Centres enregistrés avec succès.', $centres, 201);
    }
}