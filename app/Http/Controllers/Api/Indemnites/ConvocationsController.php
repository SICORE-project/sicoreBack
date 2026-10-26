<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreConvocationRequest;
use App\Http\Requests\Indemnites\UpdateConvocationRequest;
use App\Models\Indemnite\ConvocationCentre;
use App\Models\Indemnite\ConvocationCentreMetier;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\MissionDeplacement;
use App\Models\Indemnite\piece_justificatives;
use App\Models\etat_paie_indemnites;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConvocationsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        // Eager loading nécessaire à la liste DAGE (point 3 du cahier des
        // charges "Transmission des convocations") : Agent / Type / Session /
        // Centre / Rôle / Établissement proviennent tous des relations,
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

        $sessions = ConvocationModel::query()
            ->whereNotNull('session')
            ->where('session', '!=', '')
            ->distinct()
            ->orderBy('session')
            ->pluck('session')
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
            'sessions' => $sessions,
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

        if ($raison = $this->raisonRefusSuppression($convocation)) {
            return $this->error($raison, 409);
        }

        // Les pièces et les fiches de déplacement non validées disparaissent avec la convocation
        // (centres, bénéficiaires et indemnités calculées sont supprimés par la base).
        $fichiers = [];

        DB::transaction(function () use ($convocation, &$fichiers) {
            foreach (piece_justificatives::where('convocation_id', $convocation->id)->get() as $piece) {
                if ($piece->chemin) {
                    $fichiers[] = $piece->chemin;
                }

                $piece->delete();
            }

            foreach (MissionDeplacement::with('justificatifs')->where('convocation_id', $convocation->id)->get() as $mission) {
                foreach ($mission->justificatifs as $justificatif) {
                    if ($justificatif->chemin) {
                        $fichiers[] = $justificatif->chemin;
                    }
                }

                $mission->delete();
            }

            $convocation->delete();
        });

        if ($fichiers !== []) {
            Storage::disk('public')->delete($fichiers);
        }

        return $this->success('Convocation supprimée avec succès.');
    }

    /**
     * Une convocation ne peut pas être supprimée si un paiement en dépend :
     *  - une fiche de déplacement validée, remboursée ou clôturée ;
     *  - un état de paie de la même session et du même objet qui contient l'un de ses participants.
     */
    private function raisonRefusSuppression(ConvocationModel $convocation): ?string
    {
        $fichesPayees = MissionDeplacement::where('convocation_id', $convocation->id)
            ->whereIn('statut', ['valide', 'rembourse', 'cloture'])
            ->count();

        if ($fichesPayees > 0) {
            return "Suppression impossible : {$fichesPayees} fiche(s) de déplacement validée(s) ou remboursée(s) sont liées à cette convocation.";
        }

        $participants = DB::table('convocation_enseignant')->where('convocation_id', $convocation->id)->pluck('enseignant_id')
            ->merge(DB::table('convocation_centres')->where('convocation_id', $convocation->id)->pluck('chef_centre_id'))
            ->merge(DB::table('convocation_centres')->where('convocation_id', $convocation->id)->pluck('president_jury_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        foreach (etat_paie_indemnites::all() as $etat) {
            // Un état concerne la convocation si la session ET l'objet qu'il précise correspondent.
            $session = $etat->session;
            $objet = $etat->perimetre['objet'] ?? null;

            $concerne = ($session || $objet)
                && (! $session || $session === $convocation->session)
                && (! $objet || $objet === $convocation->objet);

            if (! $concerne) {
                continue;
            }

            $dansEtat = collect($etat->details ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id);

            if ($dansEtat->intersect($participants)->isNotEmpty()) {
                return "Suppression impossible : cette convocation figure dans l'état de paie {$etat->reference}.";
            }
        }

        return null;
    }
}
