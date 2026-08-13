<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationMetierRequest;
use App\Models\Indemnite\ConvocationCentre;

/**
 * Metiers/specialites d'UN centre d'examen (un centre peut en couvrir
 * plusieurs, chacun avec ses propres membres du jury — voir
 * ConvocationCentreMetier et Convocations::enseignants() dont le pivot
 * porte centre_metier_id).
 */
class ConvocationMetierController extends Controller
{
    use ApiResponseTrait;

    public function store(StoreConvocationMetierRequest $request, string $id, string $centreId)
    {
        $centre = $this->trouverCentre($id, $centreId);

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $metier = $centre->metiers()->create($request->validated());

        return $this->success('Métier ajouté avec succès.', $metier, 201);
    }

    public function update(StoreConvocationMetierRequest $request, string $id, string $centreId, string $metierId)
    {
        $metier = $this->trouverMetier($id, $centreId, $metierId);

        if (! $metier) {
            return $this->error('Métier introuvable pour ce centre.', 404);
        }

        $metier->update($request->validated());

        return $this->success('Métier mis à jour avec succès.', $metier);
    }

    public function destroy(string $id, string $centreId, string $metierId)
    {
        $metier = $this->trouverMetier($id, $centreId, $metierId);

        if (! $metier) {
            return $this->error('Métier introuvable pour ce centre.', 404);
        }

        // Les membres qui y etaient rattaches ne sont pas supprimes, seul
        // leur rattachement a ce metier l'est (meme esprit que
        // ConvocationCentreController::destroy pour les centres).
        $metier->delete();

        return $this->success('Métier supprimé avec succès.');
    }

    private function trouverCentre(string $convocationId, string $centreId): ?ConvocationCentre
    {
        return ConvocationCentre::where('convocation_id', $convocationId)->find($centreId);
    }

    private function trouverMetier(string $convocationId, string $centreId, string $metierId)
    {
        $centre = $this->trouverCentre($convocationId, $centreId);

        return $centre?->metiers()->find($metierId);
    }
}
