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

        $centres = collect($request->validated('centres'))->map(function (array $donnees) use ($convocation) {
            $centre = $convocation->centres()->create($donnees);

            // Un centre peut couvrir plusieurs metiers (wizard de creation -
            // "centres.*.metiers", un par groupe metier de la carte centre)
            // ou un seul (formulaire "Ajouter un centre" de edit.blade.php -
            // "centres.*.metier", champ unique de retrocompatibilite). Les
            // noms vides sont ignores (groupe "general" du wizard, sans
            // metier associe — ses membres restent simplement sans
            // centre_metier_id, cf. grouperBeneficiairesParMetier() cote
            // front qui les regroupe sous "Non classés").
            //
            // IMPORTANT : les metiers CREES sont renvoyes dans LE MEME ORDRE
            // que soumis (noms vides exclus, pas juste "skip" en gardant un
            // trou) — le wizard front s'appuie sur cet ordre (compteur
            // "metierPosition", qui n'avance lui aussi que pour les groupes
            // AVEC un nom) pour rattacher chaque membre au bon metier reel
            // une fois cree (id encore inconnu au moment de la saisie).
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

    /**
     * Modifie UN centre d'examen deja rattache a la convocation (fiche
     * "Modifier"). $centreId est cherche via la relation ->centres() pour
     * garantir qu'il appartient bien a CETTE convocation.
     */
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

    /**
     * Supprime UN centre d'examen. Les beneficiaires rattaches a ce
     * centre (convocation_enseignant.centre_id) restent, seul leur
     * rattachement au centre est retire (colonne nullOnDelete, voir
     * migration add_centre_id_to_convocation_enseignant_table).
     */
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