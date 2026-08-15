<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationCentresRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\ConvocationCentre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function update(Request $request, string $id, string $centreId)
    {
        $centre = ConvocationCentre::where('convocation_id', $id)->find($centreId);

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        $data = $request->validate([
            'centre' => ['required', 'string', 'max:255'],
            'jury' => ['nullable', 'string', 'max:100'],
            'metier' => ['nullable', 'string', 'max:255'],
            'chef_centre_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'chef_centre_telephone' => ['nullable', 'string', 'max:30'],
            'president_jury_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'president_jury_telephone' => ['nullable', 'string', 'max:30'],
        ]);

        $centre->update($data);

        return $this->success('Centre mis à jour avec succès.', $centre);
    }

    /**
     * Supprime UN centre d'une convocation, sans toucher aux autres centres
     * ni supprimer la convocation elle-meme (voir demande utilisateur :
     * "je ne veux pas que la suppression d'une convocation entraine la
     * suppression de tous les centres differents"). Les membres du jury
     * rattaches a ce centre ne sont pas supprimes de la convocation, seul
     * leur rattachement au centre/metier est retire.
     *
     * Nettoyage fait explicitement ici (pas de cascade au niveau base) car
     * convocation_centres/convocation_centre_metiers/convocation_enseignant
     * sont en MyISAM, moteur qui n'applique pas les contraintes de cle
     * etrangere declarees dans les migrations (cascadeOnDelete/nullOnDelete
     * y sont silencieusement ignorees).
     */
    public function destroy(string $id, string $centreId)
    {
        $centre = ConvocationCentre::where('convocation_id', $id)->find($centreId);

        if (! $centre) {
            return $this->error('Centre introuvable pour cette convocation.', 404);
        }

        DB::transaction(function () use ($centre) {
            DB::table('convocation_enseignant')
                ->where('centre_id', $centre->id)
                ->update(['centre_id' => null, 'centre_metier_id' => null]);

            $centre->metiers()->delete();
            $centre->delete();
        });

        return $this->success('Centre supprimé avec succès.');
    }
}