<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreIndemniteRequest;
use App\Http\Requests\Indemnites\UpdateIndemniteRequest;
use App\Http\Requests\Indemnites\CalculerIndemniteRequest;
use App\Http\Requests\Indemnites\ValiderCalculIndemniteRequest;
use App\Http\Requests\Indemnites\AjouterFraisIndemniteRequest;
use App\Models\indemnite\Convocations;
use App\Models\indemnite\indemnites;
use App\Models\Parametrage\Enseignant;
use App\Models\indemnite\type_indemnites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndemnitesController extends Controller
{
    use ApiResponseTrait;

    /**
     * Liste paginée des indemnités, avec filtres optionnels.
     */
    public function index(Request $request)
    {
        $query = indemnites::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('utilisateur_id')) {
            $query->where('utilisateur_id', $request->query('utilisateur_id'));
        }

        if ($request->filled('type_indemnite_id')) {
            $query->where('type_indemnite_id', $request->query('type_indemnite_id'));
        }

        $indemnites = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des indemnités.', $indemnites);
    }

    public function store(StoreIndemniteRequest $request)
    {
        $data = $request->validated();
        $data['statut'] = $data['statut'] ?? 'brouillon';

        $indemnite = indemnites::create($data);

        return $this->success('Indemnité créée avec succès.', $indemnite, 201);
    }

    public function show(string $id)
    {
        $indemnite = indemnites::find($id);

        if (! $indemnite) {
            return $this->error('Indemnité introuvable.', 404);
        }

        return $this->success('Indemnité trouvée.', $indemnite);
    }

    public function update(UpdateIndemniteRequest $request, string $id)
    {
        $indemnite = indemnites::find($id);

        if (! $indemnite) {
            return $this->error('Indemnité introuvable.', 404);
        }

        if ($indemnite->statut === 'valide') {
            return $this->error('Impossible de modifier une indemnité déjà validée.', 422);
        }

        $indemnite->update($request->validated());

        return $this->success('Indemnité mise à jour avec succès.', $indemnite);
    }

    public function destroy(string $id)
    {
        $indemnite = indemnites::find($id);

        if (! $indemnite) {
            return $this->error('Indemnité introuvable.', 404);
        }

        if ($indemnite->statut === 'valide') {
            return $this->error('Impossible de supprimer une indemnité déjà validée.', 422);
        }

        $indemnite->delete();

        return $this->success('Indemnité supprimée avec succès.');
    }


    public function calculer(CalculerIndemniteRequest $request)
    {
        $data = $request->validated();

        [$type, $erreur, $codeErreur] = $this->resoudreTypeIndemnite($data);

        if (! $type) {
            return $this->error($erreur, $codeErreur);
        }

        $detail = $this->calculerMontant($type, $data);

        $indemnite = DB::transaction(function () use ($data, $detail, $type) {
            return indemnites::create([
                'utilisateur_id' => $data['utilisateur_id'],
                'type_indemnite_id' => $type->id,
                'convocation_id' => $data['convocation_id'] ?? null,
                'enseignant_id' => $data['enseignant_id'] ?? null,
                'montant_base' => $detail['montant_base'],
                'frais_deplacement' => $detail['frais_deplacement'],
                'montant_total' => $detail['montant_total'],
                'statut' => 'calcule',
                'nombre_copies' => $data['nombre_copies'] ?? null,
                'ordre_de_mission' => $data['ordre_de_mission'] ?? false,
                'lieu_affectation' => $data['lieu_affectation'] ?? null,
                'indice' => $data['indice'] ?? null,
                'nombre_heures' => $data['nombre_heures'] ?? null,
                'nombre_kilometrages' => $data['nombre_kilometrages'] ?? null,
            ]);
        });

        return $this->success('Indemnité calculée avec succès.', $indemnite, 201);
    }

    /**
     * Simule le calcul sans persister aucune donnée (permet un aperçu avant validation).
     */
    public function simuler(CalculerIndemniteRequest $request)
    {
        $data = $request->validated();

        [$type, $erreur, $codeErreur] = $this->resoudreTypeIndemnite($data);

        if (! $type) {
            return $this->error($erreur, $codeErreur);
        }

        $detail = $this->calculerMontant($type, $data);

        return $this->success('Simulation effectuée avec succès.', $detail);
    }

    /**
     * Valide le calcul d'une indemnité existante.
     * NOTE: la route ne comporte pas d'{id}, l'identifiant est donc lu dans le corps de la requête.
     */
    public function validerCalcul(ValiderCalculIndemniteRequest $request)
    {
        $indemnite = indemnites::find($request->validated('id'));

        if (! $indemnite) {
            return $this->error('Indemnité introuvable.', 404);
        }

        $indemnite->update([
            'statut' => 'valide',
            'valide_par' => $request->user()?->id,
            'valide_at' => now(),
            'commentaire_validation' => $request->validated('commentaire_validation'),
        ]);

        return $this->success('Calcul validé avec succès.', $indemnite);
    }


    public function ajouterFrais(AjouterFraisIndemniteRequest $request, string $id)
    {
        $indemnite = indemnites::find($id);

        if (! $indemnite) {
            return $this->error('Indemnité introuvable.', 404);
        }

        $montant = $request->validated('montant');

        $indemnite->frais_deplacement = (float) $indemnite->frais_deplacement + (float) $montant;
        $indemnite->montant_total = (float) $indemnite->montant_base + (float) $indemnite->frais_deplacement;
        $indemnite->save();

        return $this->success('Frais ajouté avec succès.', $indemnite);
    }

    /**
     * Retourne le détail des frais actuellement associés à l'indemnité.
     * Voir la note de ajouterFrais() concernant l'absence de table dédiée.
     */
    public function listeFrais(string $id)
    {
        $indemnite = indemnites::find($id);

        if (! $indemnite) {
            return $this->error('Indemnité introuvable.', 404);
        }

        return $this->success('Frais de l\'indemnité.', [
            'indemnite_id' => $indemnite->id,
            'montant_base' => $indemnite->montant_base,
            'frais_deplacement' => $indemnite->frais_deplacement,
            'montant_total' => $indemnite->montant_total,
        ]);
    }


    private function resoudreTypeIndemnite(array $data): array
    {
        if (! empty($data['type_indemnite_id'])) {
            $type = type_indemnites::find($data['type_indemnite_id']);

            return $type
                ? [$type, null, null]
                : [null, "Type d'indemnité (barème) introuvable.", 404];
        }

        $convocation = ! empty($data['convocation_id'])
            ? Convocations::find($data['convocation_id'])
            : null;

        $enseignant = ! empty($data['enseignant_id'])
            ? Enseignant::find($data['enseignant_id'])
            : null;

        $typeConvocationId = $convocation?->type_convocation_id;
        $categoriePersonnel = $enseignant?->categorie_personnel;

        $candidats = type_indemnites::candidatsPour($typeConvocationId, $categoriePersonnel)->get();

        if ($candidats->isEmpty()) {
            return [
                null,
                "Aucun barème ne correspond à cette combinaison (type de convocation / catégorie de personnel). "
                    . "Créez un barème adapté, ou un barème générique (sans critère) qui servira de valeur par défaut.",
                422,
            ];
        }

        $parSpecificite = $candidats->groupBy(function (type_indemnites $t) {
            return (int) ($t->type_convocation_id !== null) + (int) ($t->categorie_personnel !== null);
        });

        $meilleurs = $parSpecificite->get($parSpecificite->keys()->max());

        if ($meilleurs->count() > 1) {
            return [
                null,
                'Plusieurs barèmes de même niveau de spécificité correspondent (ids : '
                    . $meilleurs->pluck('id')->implode(', ')
                    . '). Corrigez le paramétrage des barèmes avant de recalculer.',
                422,
            ];
        }

        return [$meilleurs->first(), null, null];
    }

    /**
     * Logique de calcul partagée entre calculer() et simuler().
     */
    private function calculerMontant(type_indemnites $type, array $data): array
    {
        $montantBase = match ($type->mode_calcul) {
            'forfaitaire' => (float) $type->montant_forfaitaire,
            'horaire' => (float) $type->taux_horaire * (float) ($data['nombre_heures'] ?? 0),
            'kilometrique' => (float) $type->taux_kilometrique * (float) ($data['nombre_kilometrages'] ?? 0),
            default => 0.0,
        };

        if ($type->plafond !== null && $montantBase > (float) $type->plafond) {
            $montantBase = (float) $type->plafond;
        }

        $fraisDeplacement = (float) ($data['frais_deplacement'] ?? 0);
        $montantTotal = $montantBase + $fraisDeplacement;

        return [
            'type_indemnite_id' => $type->id,
            'mode_calcul' => $type->mode_calcul,
            'montant_base' => round($montantBase, 2),
            'frais_deplacement' => round($fraisDeplacement, 2),
            'montant_total' => round($montantTotal, 2),
        ];
    }
}
