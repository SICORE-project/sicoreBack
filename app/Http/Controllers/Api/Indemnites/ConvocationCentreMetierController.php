<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationCentreMetierRequest;
use App\Http\Requests\Indemnites\UpdateConvocationCentreMetierRequest;
use App\Models\Indemnite\ConvocationCentre;
use App\Models\Indemnite\Convocations as ConvocationModel;


class ConvocationCentreMetierController extends Controller
{
    use ApiResponseTrait;

    public function store(StoreConvocationCentreMetierRequest $request, string $id, string $centreId)
    {
        $centre = $this->trouverCentre($id, $centreId);

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $metier = $centre->metiers()->create($request->validated());

        return $this->success('Métier ajouté avec succès.', $metier, 201);
    }

    public function update(UpdateConvocationCentreMetierRequest $request, string $id, string $centreId, string $metierId)
    {
        $centre = $this->trouverCentre($id, $centreId);

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $metier = $centre->metiers()->find($metierId);

        if (! $metier) {
            return $this->error('Métier introuvable pour ce centre.', 404);
        }

        $metier->update($request->validated());

        return $this->success('Métier mis à jour avec succès.', $metier);
    }

    
    public function destroy(string $id, string $centreId, string $metierId)
    {
        $centre = $this->trouverCentre($id, $centreId);

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $metier = $centre->metiers()->find($metierId);

        if (! $metier) {
            return $this->error('Métier introuvable pour ce centre.', 404);
        }

        $metier->delete();

        return $this->success('Métier supprimé avec succès.');
    }

    private function trouverCentre(string $id, string $centreId): ?ConvocationCentre
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return null;
        }

        return $convocation->centres()->find($centreId);
    }
}
