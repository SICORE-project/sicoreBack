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


class ConvocationImportController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ConvocationWordTemplateService $modeles
    ) {}

    public function store(ImportConvocationsRequest $request)
    {
        $utilisateurId = (int) $request->validated('utilisateur_id');

        $donnees = $this->modeles->lire($request->file('fichier')->getRealPath());
        $avertissements = $donnees['avertissements'];

        // Plus de type de convocation choisi dans le formulaire d'import :
        // chaque membre a son propre type (président de jury, président de
        // centre, correction, surveillant), déduit de son rôle dans le
        // document Word — voir resoudreMembre()/resoudreCentre() côté
        // ConvocationWordTemplateService. type_convocation_id reste nullable
        // sur la table convocations (StoreConvocationRequest).
        $dataConvocation = array_merge($donnees['convocation'], [
            'utilisateur_id' => $utilisateurId,
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

            [$centresCrees, $metiersCrees] = $this->creerCentres($convocation, $donnees['centres'], $avertissements);
            $this->attacherBeneficiaires($convocation, $donnees['beneficiaires'], $centresCrees, $metiersCrees, $avertissements);

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
     * Renvoie les centres créés ET leur métier créé (colonne "Métier /
     * spécialité" du tableau Centres — un seul métier par centre importé),
     * tous deux indexés par nom de centre (normalisé), pour le
     * rattachement des membres du jury.
     *
     * @return array{0: array<string, \App\Models\Indemnite\ConvocationCentre>, 1: array<string, \App\Models\Indemnite\ConvocationCentreMetier>}
     */
    private function creerCentres(ConvocationModel $convocation, array $centres, array &$avertissements): array
    {
        if (empty($centres)) {
            return [[], []];
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
        $metiersCrees = [];

        foreach ($centres as $index => $donnees) {
            if (in_array($index, $indexInvalides, true)) {
                continue;
            }

            $nomNormalise = $this->normaliserNom($donnees['centre']);

            // Plusieurs lignes du tableau Word peuvent partager le même nom
            // de centre (un centre qui couvre plusieurs métiers, saisi sur
            // une ligne par métier) : une seule ConvocationCentre est créée
            // par nom, les lignes suivantes du même nom n'ajoutent qu'un
            // métier supplémentaire dessus. Sans ça, chaque ligne créait sa
            // propre ConvocationCentre, indexée par nom dans $centresCrees
            // ci-dessous — la ligne suivante du même nom écrasait l'entrée
            // précédente, si bien que tous les membres rattachés à ce nom de
            // centre finissaient attachés à la DERNIÈRE ConvocationCentre
            // créée, et les précédentes restaient visibles mais vides
            // (aucun membre) : symptôme "je ne vois que le centre conservé".
            $centre = $centresCrees[$nomNormalise] ?? $convocation->centres()->create($donnees);
            $centresCrees[$nomNormalise] = $centre;

            // Un seul métier par ligne importée (colonne "Métier /
            // spécialité" du tableau Centres) : sans ce sous-enregistrement,
            // les membres rattachés à ce centre ne peuvent pas être reliés à
            // un métier (centre_metier_id), et n'apparaissent pas groupés
            // sur la fiche de la convocation — voir attacherBeneficiaires().
            if (! empty($donnees['metier'])) {
                $metiersCrees[$nomNormalise] = $centre->metiers()->create(['metier' => $donnees['metier']]);
            }
        }

        return [$centresCrees, $metiersCrees];
    }

    /**
     * Rattache les bénéficiaires reconnus (mêmes règles et même appel
     * syncWithoutDetaching que ConvocationBeneficiaireController::store).
     * Le centre de chaque membre est retrouvé par nom parmi les centres
     * qui viennent d'être créés (colonne "Centre" du tableau Membres).
     */
    private function attacherBeneficiaires(ConvocationModel $convocation, array $membres, array $centresCrees, array $metiersCrees, array &$avertissements): void
    {
        if (empty($membres)) {
            return;
        }

        $beneficiaires = [];

        foreach ($membres as $membre) {
            $centreId = null;
            $centreMetierId = null;

            if (! empty($membre['centre_nom'])) {
                $nomNormalise = $this->normaliserNom($membre['centre_nom']);
                $centre = $centresCrees[$nomNormalise] ?? null;

                if ($centre) {
                    $centreId = $centre->id;
                    $centreMetierId = $metiersCrees[$nomNormalise]->id ?? null;
                } else {
                    $avertissements[] = "membre rattaché au centre « {$membre['centre_nom']} » introuvable parmi les centres importés.";
                }
            }

            $beneficiaires[] = [
                'enseignant_id' => $membre['enseignant_id'],
                'fonction' => $membre['fonction'],
                'centre_id' => $centreId,
                'centre_metier_id' => $centreMetierId,
                'provenance' => $membre['provenance'],
                'categorie_personnel' => $membre['categorie_personnel'],
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
                'centre_metier_id' => $b['centre_metier_id'],
                'provenance' => $b['provenance'],
                'categorie_personnel' => $b['categorie_personnel'],
            ],
        ])->all();

        $convocation->enseignants()->syncWithoutDetaching($sync);
    }

    private function normaliserNom(string $valeur): string
    {
        return \Illuminate\Support\Str::of($valeur)->lower()->ascii()->trim()->toString();
    }
}
