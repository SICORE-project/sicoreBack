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
     * Supprime UN centre d'une convocation, sans toucher aux AUTRES centres
     * (voir demande utilisateur : "je ne veux pas que la suppression d'une
     * convocation entraine la suppression de tous les centres differents").
     * Les membres du jury rattaches a ce centre ne sont pas supprimes de la
     * convocation, seul leur rattachement au centre/metier est retire.
     *
     * Si c'etait le DERNIER centre de la convocation, la convocation
     * elle-meme est supprimee avec : la laisser exister sans aucun centre
     * ne sert a rien et ne fait que trainer dans la liste avec un tiret
     * dans la colonne Centre — voir retour utilisateur (capture d'ecran
     * d'une convocation "fantome" apres suppression de son unique centre).
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