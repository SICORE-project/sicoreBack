<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationCentreMetierRequest;
use App\Http\Requests\Indemnites\UpdateConvocationCentreMetierRequest;
use App\Models\Indemnite\ConvocationCentre;
use App\Models\Indemnite\Convocations as ConvocationModel;

/**
 * Metiers d'UN centre d'examen (cf. modele papier "convocation jury BT" :
 * un centre peut couvrir plusieurs metiers, chacun avec ses propres
 * membres du jury). Imbrique sous
 * /convocations/{id}/centres/{centreId}/metiers pour garantir que le
 * metier appartient bien a CE centre de CETTE convocation.
 */
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

    /**
     * Supprime UN metier. Les beneficiaires qui y etaient rattaches
     * (convocation_enseignant.centre_metier_id) restent, seul leur
     * rattachement a ce metier est retire (colonne nullOnDelete, voir
     * migration add_centre_metier_id_to_convocation_enseignant_table).
     */
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
