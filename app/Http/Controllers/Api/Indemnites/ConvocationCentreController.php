<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationCentresRequest;
use App\Http\Requests\Indemnites\UpdateConvocationCentreRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\ConvocationCentre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConvocationCentreController extends Controller
{
    use ApiResponseTrait;

    public function index(string $id)
    {
        $convocation = ConvocationModel::trouverParSlugOuId($id);

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
        $convocation = ConvocationModel::trouverParSlugOuId($id);

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
        $convocation = ConvocationModel::trouverParSlugOuId($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $centre = $convocation->centres()->slugOuId($centreId)->first();

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $data = $request->validate([
            'centre' => ['required', 'string', 'max:255'],
            'jury' => ['nullable', 'string', 'max:100'],
            'metier' => ['nullable', 'string', 'max:255'],
            'chef_centre_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'chef_centre_telephone' => ['nullable', 'string', 'max:30'],
            'chef_centre_provenance' => ['nullable', 'string', 'max:255'],
            'chef_centre_categorie_personnel' => ['nullable', 'in:fonctionnaire,contractuel,vacataire'],
            'president_jury_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'president_jury_telephone' => ['nullable', 'string', 'max:30'],
            'president_jury_provenance' => ['nullable', 'string', 'max:255'],
            'president_jury_categorie_personnel' => ['nullable', 'in:fonctionnaire,contractuel,vacataire'],
        ]);

        $centre->update($data);

        return $this->success('Centre mis à jour avec succès.', $centre);
    }

   
    public function destroy(string $id, string $centreId)
    {
        $convocation = ConvocationModel::trouverParSlugOuId($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $centre = $convocation->centres()->slugOuId($centreId)->first();

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $convocationSupprimee = DB::transaction(function () use ($centre) {
            DB::table('convocation_enseignant')
                ->where('centre_id', $centre->id)
                ->update(['centre_id' => null, 'centre_metier_id' => null]);

            $centre->metiers()->delete();

            $convocation = $centre->convocation;
            $centre->delete();

            $etaitLeDernier = $convocation->centres()->doesntExist();

            if ($etaitLeDernier) {
                $convocation->delete();
            }

            return $etaitLeDernier;
        });

        return $this->success(
            $convocationSupprimee
                ? "Centre supprimé — c'était le dernier de cette convocation, elle a donc été supprimée aussi."
                : 'Centre supprimé avec succès.'
        );
    }
}