<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\AttachBeneficiairesConvocationRequest;
use App\Http\Requests\Indemnites\UpdateConvocationBeneficiaireRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Parametrage\Enseignant;

class ConvocationBeneficiaireController extends Controller
{
    use ApiResponseTrait;

    public function index(string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

       
        return $this->success(
            'Bénéficiaires de la convocation.',
            $convocation->enseignants()->get()
        );
    }

    public function store(AttachBeneficiairesConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $beneficiaires = $request->validated('beneficiaires');

        // "UN BENEFICIAIRE NE PEUT PAS ETRE CONVOQUE PLUS DE UNE FOIS" :
        // AttachBeneficiairesConvocationRequest ("distinct") empeche deja
        // les doublons AU SEIN de cette requete, mais pas un enseignant
        // DEJA rattache a la convocation (ajoute lors d'un appel
        // precedent) - sans ce garde-fou, syncWithoutDetaching() ecraserait
        // silencieusement sa fonction/son centre au lieu de signaler le
        // doublon.
        $enseignantIdsSoumis = collect($beneficiaires ?: $request->validated('enseignant_ids'))
            ->map(fn ($b) => is_array($b) ? ($b['enseignant_id'] ?? null) : $b)
            ->filter()
            ->all();

        $dejaConvoques = $convocation->enseignants()
            ->whereIn('enseignant_id', $enseignantIdsSoumis)
            ->get(['nom', 'prenom']);

        if ($dejaConvoques->isNotEmpty()) {
            $noms = $dejaConvoques->map(fn ($e) => trim("{$e->prenom} {$e->nom}"))->implode(', ');

            return $this->error(
                "Déjà convoqué(e) sur cette convocation : {$noms}. Un bénéficiaire ne peut pas être convoqué plus d'une fois.",
                422
            );
        }

        if ($beneficiaires) {
            // Les centre_id / centre_metier_id valides doivent appartenir a
            // CETTE convocation (les regles 'exists:...' ne verifient que
            // l'existence de la ligne, pas son rattachement).
            $centresValides = $convocation->centres()->pluck('id')->all();
            $metiersValides = \App\Models\Indemnite\ConvocationCentreMetier::whereIn('convocation_centre_id', $centresValides)->pluck('id')->all();

            // Nouveau format : [ ['enseignant_id' => 1, 'fonction' => 'President de jury', 'centre_id' => 3, 'centre_metier_id' => 7], ... ]
            $sync = collect($beneficiaires)->mapWithKeys(function (array $b) use ($centresValides, $metiersValides) {
                $centreId = $b['centre_id'] ?? null;
                $centreMetierId = $b['centre_metier_id'] ?? null;

                if ($centreId && ! in_array($centreId, $centresValides, true)) {
                    $centreId = null;
                }

                if ($centreMetierId && ! in_array($centreMetierId, $metiersValides, true)) {
                    $centreMetierId = null;
                }

                return [
                    $b['enseignant_id'] => [
                        'fonction' => $b['fonction'] ?? null,
                        'centre_id' => $centreId,
                        'centre_metier_id' => $centreMetierId,
                        'provenance' => $b['provenance'] ?? null,
                    ],
                ];
            })->all();

            // categorie_personnel (fonctionnaire/contractuel/vacataire) est
            // un attribut de l'ENSEIGNANT, pas de cette convocation (deja
            // present sur `enseignants`) : on le met a jour directement sur
            // sa fiche plutot que de le dupliquer sur le pivot.
            foreach ($beneficiaires as $b) {
                if (! empty($b['categorie_personnel']) && ! empty($b['enseignant_id'])) {
                    Enseignant::where('id', $b['enseignant_id'])
                        ->update(['categorie_personnel' => $b['categorie_personnel']]);
                }
            }
        } else {
            // Ancien format : liste brute d'IDs, sans fonction ni centre.
            $sync = collect($request->validated('enseignant_ids'))->mapWithKeys(fn ($enseignantId) => [
                $enseignantId => ['fonction' => null, 'centre_id' => null, 'centre_metier_id' => null, 'provenance' => null],
            ])->all();
        }

        $convocation->enseignants()->syncWithoutDetaching($sync);

        return $this->success(
            'Bénéficiaires ajoutés avec succès.',
            $convocation->enseignants()->get(),
            201
        );
    }

   
    public function update(UpdateConvocationBeneficiaireRequest $request, string $id, string $enseignantId)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $estRattache = $convocation->enseignants()->where('enseignant_id', $enseignantId)->exists();

        if (! $estRattache) {
            return $this->error('Bénéficiaire introuvable pour cette convocation.', 404);
        }

        $data = $request->validated();

        // centre_id / centre_metier_id doivent appartenir a CETTE
        // convocation (meme garde que store()).
        $centreId = $data['centre_id'] ?? null;
        if ($centreId && ! $convocation->centres()->where('id', $centreId)->exists()) {
            $centreId = null;
        }

        $centreMetierId = $data['centre_metier_id'] ?? null;
        if ($centreMetierId) {
            $centresValides = $convocation->centres()->pluck('id')->all();
            $appartientALaConvocation = \App\Models\Indemnite\ConvocationCentreMetier::where('id', $centreMetierId)
                ->whereIn('convocation_centre_id', $centresValides)
                ->exists();

            if (! $appartientALaConvocation) {
                $centreMetierId = null;
            }
        }

        $convocation->enseignants()->updateExistingPivot($enseignantId, [
            'fonction' => $data['fonction'] ?? null,
            'centre_id' => $centreId,
            'centre_metier_id' => $centreMetierId,
            'provenance' => $data['provenance'] ?? null,
        ]);

        if (! empty($data['categorie_personnel'])) {
            Enseignant::where('id', $enseignantId)->update([
                'categorie_personnel' => $data['categorie_personnel'],
            ]);
        }

        return $this->success('Bénéficiaire mis à jour avec succès.', $convocation->enseignants()->get());
    }

    /**
     * Retire un beneficiaire de la convocation (detach du pivot — ne
     * supprime pas l'enseignant lui-meme).
     */
    public function destroy(string $id, string $enseignantId)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $convocation->enseignants()->detach($enseignantId);

        return $this->success('Bénéficiaire retiré avec succès.');
    }
}
