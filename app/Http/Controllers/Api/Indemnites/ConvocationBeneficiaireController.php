<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\AttachBeneficiairesConvocationRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;

class ConvocationBeneficiaireController extends Controller
{
    use ApiResponseTrait;

    public function index(string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        // Liste COMPLETE, pas paginee : le front (renderShow() /
        // renderEdit() dans sicoreFront) filtre et regroupe cette liste par
        // centre/metier cote client (voir grouperBeneficiairesParMetier()),
        // il attend 'data' directement comme tableau de beneficiaires — pas
        // un objet paginateur imbrique. Un centre a rarement plus de
        // quelques dizaines de membres, la pagination n'apporte rien ici.
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

        if ($beneficiaires) {
            // Les centre_id/centre_metier_id valides doivent appartenir a
            // CETTE convocation (les regles 'exists:...' ne verifient que
            // l'existence de la ligne, pas son rattachement).
            $centresValides = $convocation->centres()->pluck('id')->all();
            $metiersValides = \App\Models\Indemnite\ConvocationCentreMetier::whereIn('convocation_centre_id', $centresValides)
                ->pluck('id')->all();

            // Nouveau format : [ ['enseignant_id' => 1, 'fonction' => 'President de jury', 'centre_id' => 3, 'centre_metier_id' => 5], ... ]
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
}