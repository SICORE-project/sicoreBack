<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\AttachBeneficiairesConvocationRequest;
use App\Http\Requests\Indemnites\ImportConvocationsRequest;
use App\Http\Requests\Indemnites\StoreConvocationCentresRequest;
use App\Http\Requests\Indemnites\StoreConvocationRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Services\Indemnites\ConvocationWordTemplateService as Modele;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Import d'une convocation depuis le modèle Word rempli. Chaque erreur ou
 * avertissement renvoyé indique où se trouve le problème dans le document :
 * tableau, ligne (1 = première ligne remplie sous l'en-tête) et colonne.
 */
class ConvocationImportController extends Controller
{
    use ApiResponseTrait;

    private const MESSAGES = [
        'required' => ':attribute est obligatoire.',
        'date' => ':attribute : date invalide (format attendu : jj/mm/aaaa).',
        'after_or_equal' => ':attribute doit être postérieure ou égale à la :date.',
        'date_format' => ':attribute : format attendu :format.',
        'max.string' => ':attribute ne doit pas dépasser :max caractères.',
        'in' => ':attribute : valeur non autorisée.',
        'exists' => ':attribute : valeur introuvable dans la base.',
        'integer' => ':attribute doit être un nombre entier.',
        'string' => ':attribute doit être du texte.',
        'array' => ':attribute : format invalide.',
        'boolean' => ':attribute doit valoir oui ou non.',
    ];

    public function __construct(
        private readonly Modele $modeles
    ) {}

    public function store(ImportConvocationsRequest $request)
    {
        $utilisateurId = (int) $request->validated('utilisateur_id');

        try {
            $donnees = $this->modeles->lire($request->file('fichier')->getRealPath());
        } catch (\Throwable $e) {
            report($e);

            return $this->error(
                "Le fichier n'a pas pu être lu : vérifiez qu'il s'agit bien d'un document Word (.docx) issu du modèle.",
                422,
                ["Fichier : document Word illisible ou corrompu."]
            );
        }

        $avertissements = $donnees['avertissements'];

        // Chaque membre a son propre type (président de jury, président de
        // centre, correction, surveillant), déduit de son rôle dans le
        // document : type_convocation_id reste nullable.
        $dataConvocation = array_merge($donnees['convocation'], [
            'utilisateur_id' => $utilisateurId,
        ]);

        $validateurConvocation = Validator::make(
            $dataConvocation,
            (new StoreConvocationRequest())->rules(),
            self::MESSAGES,
            $this->attributs(Modele::TABLEAU_INFOS, array_keys((new StoreConvocationRequest())->rules()))
        );

        if ($validateurConvocation->fails()) {
            $erreurs = array_map(
                fn (string $erreur) => Modele::TABLEAU_INFOS." : {$erreur}",
                $validateurConvocation->errors()->all()
            );

            return $this->error(
                "Import refusé : le tableau « ".Modele::TABLEAU_INFOS." » est incomplet ou invalide.",
                422,
                array_merge($erreurs, $avertissements)
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
     * Crée les centres valides (mêmes règles que StoreConvocationCentresRequest,
     * appliquées ligne par ligne). Renvoie les centres créés ET leur métier
     * (un seul métier par ligne importée), indexés par nom de centre
     * normalisé, pour le rattachement des membres du jury.
     *
     * @return array{0: array<string, \App\Models\Indemnite\ConvocationCentre>, 1: array<string, \App\Models\Indemnite\ConvocationCentreMetier>}
     */
    private function creerCentres(ConvocationModel $convocation, array $centres, array &$avertissements): array
    {
        $regles = $this->reglesParLigne((new StoreConvocationCentresRequest())->rules(), 'centres');
        $attributs = $this->attributs(Modele::TABLEAU_CENTRES, array_keys($regles));

        $centresCrees = [];
        $metiersCrees = [];

        foreach ($centres as $donnees) {
            $numero = $donnees['ligne'];
            unset($donnees['ligne']);

            $validateur = Validator::make($donnees, $regles, self::MESSAGES, $attributs);

            if ($validateur->fails()) {
                foreach ($validateur->errors()->all() as $erreur) {
                    $avertissements[] = Modele::TABLEAU_CENTRES.", ligne {$numero} : {$erreur} — centre ignoré.";
                }

                continue;
            }

            $nomNormalise = $this->normaliserNom($donnees['centre']);

            // Plusieurs lignes peuvent partager le même nom de centre (un
            // centre qui couvre plusieurs métiers, une ligne par métier) :
            // une seule ConvocationCentre par nom, les lignes suivantes
            // n'ajoutent qu'un métier dessus.
            $centre = $centresCrees[$nomNormalise] ?? $convocation->centres()->create($donnees);
            $centresCrees[$nomNormalise] = $centre;

            if (! empty($donnees['metier'])) {
                $metiersCrees[$nomNormalise] = $centre->metiers()->create(['metier' => $donnees['metier']]);
            }
        }

        return [$centresCrees, $metiersCrees];
    }

    /**
     * Rattache les membres du jury reconnus (mêmes règles que
     * ConvocationBeneficiaireController::store, appliquées ligne par ligne).
     * Le centre de chaque membre est retrouvé par nom parmi les centres
     * qui viennent d'être créés.
     */
    private function attacherBeneficiaires(ConvocationModel $convocation, array $membres, array $centresCrees, array $metiersCrees, array &$avertissements): void
    {
        $regles = $this->reglesParLigne((new AttachBeneficiairesConvocationRequest())->rules(), 'beneficiaires');
        $attributs = $this->attributs(Modele::TABLEAU_MEMBRES, array_keys($regles));

        $sync = [];
        $lignesParEnseignant = [];

        foreach ($membres as $membre) {
            $numero = $membre['ligne'];
            $emplacement = Modele::TABLEAU_MEMBRES.", ligne {$numero}";

            if (isset($lignesParEnseignant[$membre['enseignant_id']])) {
                $avertissements[] = "{$emplacement} : ce matricule figure déjà à la ligne {$lignesParEnseignant[$membre['enseignant_id']]} — doublon ignoré.";

                continue;
            }

            $centreId = null;
            $centreMetierId = null;

            if (! empty($membre['centre_nom'])) {
                $nomNormalise = $this->normaliserNom($membre['centre_nom']);
                $centre = $centresCrees[$nomNormalise] ?? null;

                if ($centre) {
                    $centreId = $centre->id;
                    $centreMetierId = $metiersCrees[$nomNormalise]->id ?? null;
                } else {
                    $avertissements[] = "{$emplacement} : colonne « Centre de rattachement » : « {$membre['centre_nom']} » ne correspond à aucun centre du tableau « ".Modele::TABLEAU_CENTRES." » — membre ajouté sans centre.";
                }
            }

            $beneficiaire = [
                'enseignant_id' => $membre['enseignant_id'],
                'fonction' => $membre['fonction'],
                'centre_id' => $centreId,
                'centre_metier_id' => $centreMetierId,
                'provenance' => $membre['provenance'],
                'categorie_personnel' => $membre['categorie_personnel'],
            ];

            $validateur = Validator::make($beneficiaire, $regles, self::MESSAGES, $attributs);

            if ($validateur->fails()) {
                foreach ($validateur->errors()->all() as $erreur) {
                    $avertissements[] = "{$emplacement} : {$erreur} — membre ignoré.";
                }

                continue;
            }

            $lignesParEnseignant[$membre['enseignant_id']] = $numero;
            $sync[$membre['enseignant_id']] = array_diff_key($beneficiaire, ['enseignant_id' => true]);
        }

        if (! empty($sync)) {
            $convocation->enseignants()->syncWithoutDetaching($sync);
        }
    }

    /**
     * Extrait d'un FormRequest les règles d'un élément de liste
     * ("centres.*.centre" → "centre"), pour valider ligne par ligne.
     * La règle "distinct" (comparaison entre éléments) n'a pas de sens sur
     * une ligne isolée : les doublons sont traités à part.
     */
    private function reglesParLigne(array $regles, string $liste): array
    {
        $resultat = [];
        $prefixe = "{$liste}.*.";

        foreach ($regles as $champ => $reglesChamp) {
            if (! str_starts_with($champ, $prefixe)) {
                continue;
            }

            $resultat[substr($champ, strlen($prefixe))] = array_values(array_filter(
                (array) $reglesChamp,
                fn ($regle) => ! is_string($regle) || ! str_starts_with($regle, 'required_with') && $regle !== 'distinct'
            ));
        }

        return $resultat;
    }

    /**
     * Noms d'attributs lisibles pour les messages de validation : l'intitulé
     * de la colonne du document Word (« colonne « Objet » »).
     */
    private function attributs(string $tableau, array $champs): array
    {
        $attributs = [];

        foreach ($champs as $champ) {
            $champModele = match ($champ) {
                'chef_centre_id' => 'chef_centre_matricule',
                'president_jury_id' => 'president_jury_matricule',
                'enseignant_id' => 'matricule',
                'centre_id', 'centre_metier_id' => 'centre_nom',
                default => $champ,
            };

            $attributs[$champ] = 'colonne « '.$this->modeles->libelleColonne($tableau, $champModele).' »';
        }

        return $attributs;
    }

    private function normaliserNom(string $valeur): string
    {
        return Str::of($valeur)->lower()->ascii()->trim()->toString();
    }
}
