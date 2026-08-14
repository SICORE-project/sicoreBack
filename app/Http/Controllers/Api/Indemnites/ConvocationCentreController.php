<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationCentresRequest;
use App\Http\Requests\Indemnites\UpdateConvocationCentreRequest;
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

    
    public function store(StoreConvocationCentresRequest $request, string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $centres = collect($request->validated('centres'))->map(function (array $donnees) use ($convocation) {
            $centre = $convocation->centres()->create($donnees);

            
            $nomsMetiers = ! empty($donnees['metiers'])
                ? $donnees['metiers']
                : (! empty($donnees['metier']) ? [$donnees['metier']] : []);

            $metiersCrees = collect();

            foreach ($nomsMetiers as $nomMetier) {
                if (! empty($nomMetier)) {
                    $metiersCrees->push($centre->metiers()->create(['metier' => $nomMetier]));
                }
            }

            $centre->setRelation('metiers', $metiersCrees->values());

            return $centre;
        });

        return $this->success('Centres enregistrés avec succès.', $centres, 201);
    }

    
    public function update(UpdateConvocationCentreRequest $request, string $id, string $centreId)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $centre = $convocation->centres()->find($centreId);

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $centre->update($request->validated());

        return $this->success('Centre mis à jour avec succès.', $centre->fresh(['chefCentre', 'presidentJury']));
    }

   
    public function destroy(string $id, string $centreId)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $centre = $convocation->centres()->find($centreId);

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $centre->delete();

        return $this->success('Centre supprimé avec succès.');
    }
}