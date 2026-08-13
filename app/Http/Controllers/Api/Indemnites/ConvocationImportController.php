<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\AttachBeneficiairesConvocationRequest;
use App\Http\Requests\Indemnites\ImportConvocationsRequest;
use App\Http\Requests\Indemnites\StoreConvocationCentresRequest;
use App\Http\Requests\Indemnites\StoreConvocationRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Services\Indemnites\ConvocationWordTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Import d'une convocation depuis le modèle Word téléchargeable (voir
 * ConvocationModeleWordController) : un document rempli décrit UNE
 * convocation complète (infos générales + centres d'examen + membres du
 * jury), avec les mêmes champs que le formulaire de saisie manuelle.
 *
 * La création réutilise exactement les mêmes règles de validation
 * (StoreConvocationRequest, StoreConvocationCentresRequest,
 * AttachBeneficiairesConvocationRequest) et les mêmes appels Eloquent que
 * ConvocationsController::store / ConvocationCentreController::store /
 * ConvocationBeneficiaireController::store — seule la source des données
 * change (fichier Word au lieu du formulaire HTML).
 *
 * Un centre ou un membre dont une donnée est invalide ou introuvable
 * (agent non reconnu, centre vide...) n'empêche pas la création de la
 * convocation : il est ignoré et remonté en avertissement, à charge pour
 * la DAGE de compléter ensuite via le formulaire (même philosophie que
 * l'ancien import CSV).
 */
class ConvocationImportController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ConvocationWordTemplateService $modeles
    ) {}

    public function store(ImportConvocationsRequest $request)
    {
        $utilisateurId = (int) $request->validated('utilisateur_id');
        $typeConvocationId = (int) $request->validated('type_convocation_id');

        $donnees = $this->modeles->lire($request->file('fichier')->getRealPath());
        $avertissements = $donnees['avertissements'];

        // Le type est choisi dans le formulaire d'import (pas dans le
        // document Word) : il prime sur toute valeur eventuellement issue
        // du fichier.
        $dataConvocation = array_merge($donnees['convocation'], [
            'utilisateur_id' => $utilisateurId,
            'type_convocation_id' => $typeConvocationId,
        ]);

        $validateurConvocation = Validator::make($dataConvocation, (new StoreConvocationRequest())->rules());

        if ($validateurConvocation->fails()) {
            return $this->error(
                "Le tableau « Informations générales » du document est incomplet ou invalide.",
                422,
                $validateurConvocation->errors()
            );
        }

        $convocation = DB::transaction(function () use ($validateurConvocation, $donnees, &$avertissements) {
            $convocation = ConvocationModel::create($validateurConvocation->validated());

            $centresCrees = $this->creerCentres($convocation, $donnees['centres'], $avertissements);
            $this->attacherBeneficiaires($convocation, $donnees['beneficiaires'], $centresCrees, $avertissements);

            return $convocation;
        });

        return $this->success(
            'Convocation importée avec succès.',
            [
                'importees' => 1,
                'convocations' => [[
                    'convocation_id' => $convocation->id,
                    'objet' => $convocation->objet,
                    'statut' => $convocation->statut,
                ]],
                'avertissements' => $avertissements,
                'erreurs' => [],
            ],
            201
        );
    }

    /**
     * Crée les centres valides (mêmes règles que
     * StoreConvocationCentresRequest, "centres" rendu facultatif ici
     * puisqu'une convocation importée peut ne pas encore en avoir).
     * Renvoie les centres créés indexés par nom (normalisé) pour le
     * rattachement des membres du jury.
     *
     * @return array<string, \App\Models\Indemnite\ConvocationCentre>
     */
    private function creerCentres(ConvocationModel $convocation, array $centres, array &$avertissements): array
    {
        if (empty($centres)) {
            return [];
        }

        $regles = (new StoreConvocationCentresRequest())->rules();
        $regles['centres'] = ['nullable', 'array'];

        $validateur = Validator::make(['centres' => $centres], $regles);

        if ($validateur->fails()) {
            foreach ($validateur->errors()->all() as $erreur) {
                $avertissements[] = "centre ignoré : {$erreur}";
            }
        }

        $indexInvalides = [];

        foreach (array_keys($validateur->failed()) as $champ) {
            if (preg_match('/^centres\.(\d+)\./', $champ, $correspondance)) {
                $indexInvalides[] = (int) $correspondance[1];
            }
        }

        $indexInvalides = array_unique($indexInvalides);

        $centresCrees = [];

        foreach ($centres as $index => $donnees) {
            if (in_array($index, $indexInvalides, true)) {
                continue;
            }

            $centre = $convocation->centres()->create($donnees);
            $centresCrees[$this->normaliserNom($donnees['centre'])] = $centre;
        }

        return $centresCrees;
    }

    /**
     * Rattache les bénéficiaires reconnus (mêmes règles et même appel
     * syncWithoutDetaching que ConvocationBeneficiaireController::store).
     * Le centre de chaque membre est retrouvé par nom parmi les centres
     * qui viennent d'être créés (colonne "Centre" du tableau Membres).
     */
    private function attacherBeneficiaires(ConvocationModel $convocation, array $membres, array $centresCrees, array &$avertissements): void
    {
        if (empty($membres)) {
            return;
        }

        $beneficiaires = [];

        foreach ($membres as $membre) {
            $centreId = null;

            if (! empty($membre['centre_nom'])) {
                $centre = $centresCrees[$this->normaliserNom($membre['centre_nom'])] ?? null;

                if ($centre) {
                    $centreId = $centre->id;
                } else {
                    $avertissements[] = "membre rattaché au centre « {$membre['centre_nom']} » introuvable parmi les centres importés.";
                }
            }

            $beneficiaires[] = [
                'enseignant_id' => $membre['enseignant_id'],
                'fonction' => $membre['fonction'],
                'centre_id' => $centreId,
                'provenance' => $membre['provenance'],
            ];
        }

        $regles = (new AttachBeneficiairesConvocationRequest())->rules();
        unset($regles['enseignant_ids'], $regles['enseignant_ids.*']);
        $regles['beneficiaires'] = ['nullable', 'array'];

        $validateur = Validator::make(['beneficiaires' => $beneficiaires], $regles);

        if ($validateur->fails()) {
            foreach ($validateur->errors()->all() as $erreur) {
                $avertissements[] = "membre du jury ignoré : {$erreur}";
            }
        }

        // Même construction du pivot que ConvocationBeneficiaireController::store
        // (centre_id déjà garanti appartenir à cette convocation, puisqu'il
        // provient de creerCentres() ci-dessus).
        $sync = collect($beneficiaires)->mapWithKeys(fn (array $b) => [
            $b['enseignant_id'] => [
                'fonction' => $b['fonction'],
                'centre_id' => $b['centre_id'],
                'provenance' => $b['provenance'],
            ],
        ])->all();

        $convocation->enseignants()->syncWithoutDetaching($sync);
    }

    private function normaliserNom(string $valeur): string
    {
        return \Illuminate\Support\Str::of($valeur)->lower()->ascii()->trim()->toString();
    }
}
