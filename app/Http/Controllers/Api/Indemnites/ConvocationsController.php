<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationRequest;
use App\Http\Requests\Indemnites\UpdateConvocationRequest;
use App\Models\Indemnite\ConvocationCentre;
use App\Models\Indemnite\ConvocationCentreMetier;
use App\Models\Indemnite\Convocations as ConvocationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ConvocationsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        // Eager loading nécessaire à la liste DAGE (point 3 du cahier des
        // charges "Transmission des convocations") : Agent / Type / Session /
        // Centre / Rôle / Lieu de service proviennent tous des relations,
        // pas seulement des colonnes de `convocations`. "centres.presidentJury"
        // et "centres.chefCentre" chargent aussi les centres eux-mêmes (pas
        // besoin de lister "centres" à part) — nécessaire à la page Pièces
        // justificatives, où le chef de centre ET le président du jury de
        // chaque centre doivent CHACUN apparaître comme un membre à part
        // entière (ils déposent eux aussi leurs pièces justificatives).
        $query = ConvocationModel::withCount('enseignants')
            ->with(['typeConvocation', 'centres.presidentJury', 'centres.chefCentre', 'enseignants.lieuService']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('utilisateur_id')) {
            $query->where('utilisateur_id', $request->query('utilisateur_id'));
        }

        if ($request->filled('objet')) {
            $query->where('objet', 'like', '%'.$request->query('objet').'%');
        }

        if ($request->filled('date')) {
            $query->whereDate('date_emission', $request->query('date'));
        }

        // Metier et Centre vivent sur convocation_centres (hasMany), pas
        // directement sur convocations : whereHas() pour ne garder que les
        // convocations ayant AU MOINS un centre correspondant au filtre.
        //
        // NB: le metier peut se trouver a DEUX endroits — l'ancienne colonne
        // convocation_centres.metier (gardee pour compatibilite, plus
        // jamais ecrite depuis le wizard riche) OU la table dediee
        // convocation_centre_metiers (ConvocationSyncController::sync(),
        // un centre peut couvrir plusieurs metiers). Ne filtrer que sur la
        // premiere ratait donc silencieusement toutes les convocations
        // creees/modifiees via le wizard "Nouvelle convocation" / "Modifier".
        if ($request->filled('metier')) {
            $metier = $request->query('metier');
            $query->where(function ($outer) use ($metier) {
                $outer->whereHas('centres', function ($q) use ($metier) {
                    $q->where('metier', 'like', '%'.$metier.'%');
                })->orWhereHas('centres.metiers', function ($q) use ($metier) {
                    $q->where('metier', 'like', '%'.$metier.'%');
                });
            });
        }

        if ($request->filled('centre')) {
            $centre = $request->query('centre');
            $query->whereHas('centres', function ($q) use ($centre) {
                $q->where('centre', 'like', '%'.$centre.'%');
            });
        }

        if ($request->filled('session')) {
            $query->where('session', $request->query('session'));
        }

        $convocations = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des convocations.', $convocations);
    }

    /**
     * Valeurs distinctes reellement presentes en base, pour remplir les
     * menus deroulants des filtres de la liste front (index.blade.php) —
     * plutot que des champs texte libre. Independant de tout filtre en
     * cours : renvoie toujours l'univers complet des valeurs possibles,
     * pas seulement celles de la page courante/filtree.
     */
    public function optionsFiltres()
    {
        $objets = ConvocationModel::query()
            ->whereNotNull('objet')
            ->where('objet', '!=', '')
            ->distinct()
            ->orderBy('objet')
            ->pluck('objet')
            ->values();

        $centres = ConvocationCentre::query()
            ->whereNotNull('centre')
            ->where('centre', '!=', '')
            ->distinct()
            ->orderBy('centre')
            ->pluck('centre')
            ->values();

        
        $metiersLegacy = ConvocationCentre::query()
            ->whereNotNull('metier')
            ->where('metier', '!=', '')
            ->distinct()
            ->pluck('metier');

        $metiersDedies = ConvocationCentreMetier::query()
            ->whereNotNull('metier')
            ->where('metier', '!=', '')
            ->distinct()
            ->pluck('metier');

        $metiers = $metiersLegacy->merge($metiersDedies)->unique()->sort()->values();

        $dates = ConvocationModel::query()
            ->whereNotNull('date_emission')
            ->distinct()
            ->orderByDesc('date_emission')
            ->pluck('date_emission')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->values();

        // Enum fixe (cf. migration convocations.statut) plutot qu'une
        // requete : toujours les 4 valeurs possibles, meme si aucune
        // convocation n'a encore tel statut.
        $statuts = [
            ['value' => 'brouillon', 'label' => 'Brouillon'],
            ['value' => 'emise', 'label' => 'Émise'],
            ['value' => 'envoyee', 'label' => 'Envoyée'],
            ['value' => 'cloturee', 'label' => 'Clôturée'],
        ];

        return $this->success('Options de filtres.', [
            'objets' => $objets,
            'centres' => $centres,
            'metiers' => $metiers,
            'dates' => $dates,
            'statuts' => $statuts,
        ]);
    }

    /**
     * Créé une nouvelle convocation.
     *
     * NOTE: le dépôt de fichier a été déplacé vers ConvocationFichierController
     * (POST /convocations/{id}/fichier) — cette méthode ne gère plus que
     * la création, ce qui évite la bifurcation conditionnelle ambiguë
     * qui existait auparavant dans StoreConvocationRequest.
     *
     * L'import en masse (option A du workflow DAGE : fichier DECPC ->
     * enregistrements) est géré séparément par ConvocationImportController.
     */
    public function store(StoreConvocationRequest $request)
    {
        $convocation = ConvocationModel::create($request->validated());

        return $this->success('Convocation créée avec succès.', $convocation, 201);
    }

    public function show(string $id)
    {
        // slugOuId() : $id est le slug opaque construit par le front dans
        // ses URLs (voir HasOpaqueSlug), mais reste aussi accepte sous
        // forme d'id numerique pour ne rien casser en interne.
        $convocation = ConvocationModel::with([
            'envois',
            'centres.chefCentre',
            'centres.presidentJury',
            'centres.metiers',
            'centres.enseignants.lieuService',
            'enseignants.lieuService',
            'typeConvocation',
        ])->slugOuId($id)->first();

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        return $this->success('Convocation trouvée.', $convocation);
    }

    public function update(UpdateConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::trouverParSlugOuId($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $convocation->update($request->validated());

        return $this->success('Convocation mise à jour avec succès.', $convocation);
    }

    public function destroy(string $id)
    {
        $convocation = ConvocationModel::trouverParSlugOuId($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $convocation->delete();

        return $this->success('Convocation supprimée avec succès.');
    }
}
