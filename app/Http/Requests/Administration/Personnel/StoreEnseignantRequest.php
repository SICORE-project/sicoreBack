<?php

namespace App\Http\Requests\Administration\Personnel;

use App\Models\Parametrage\CorpsEnseignant;
use App\Models\Parametrage\Diplome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnseignantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('nombre_femmes') && ! $this->filled('nombre_femmes')) {
            $this->merge(['nombre_femmes' => 0]);
        }
        if ($this->has('compte_bancaire.type_virement') && ! $this->filled('compte_bancaire.type_virement')) {
            $this->merge(['compte_bancaire' => array_merge($this->input('compte_bancaire', []), ['type_virement' => 'unitaire'])]);
        }
        $enfants = max(0, $this->integer('nombre_enfants'));
        $marie = $this->boolean('est_en_couple');
        $conjointTravaille = $marie && $this->boolean('conjoint_travaille');
        $diplome = $this->filled('diplome_id') ? Diplome::find($this->integer('diplome_id')) : null;
        $categorieId = $this->filled('categorie_id') ? $this->integer('categorie_id') : null;
        if ($diplome && $categorieId && (int) $diplome->categorie_id !== $categorieId) {
            $correspondance = Diplome::query()
                ->whereRaw('UPPER(TRIM(libelle)) = ?', [mb_strtoupper(trim($diplome->libelle), 'UTF-8')])
                ->where('categorie_id', $categorieId)
                ->orderBy('id')
                ->first();
            if ($correspondance) {
                $diplome = $correspondance;
                $this->merge(['diplome_id' => $diplome->id]);
            }
        }
        $this->merge([
            'nombre_enfants' => $enfants,
            'conjoint_travaille' => $conjointTravaille,
            'nombre_parts_fiscales' => min(5, max(1, 1 + ($marie ? 1 : 0) + ($enfants * .5) - ($conjointTravaille ? .5 : 0))),
            'salaire_brut' => $this->corpsEstVacataire() ? 150000 : ($diplome && (($categorieId && (int) $diplome->categorie_id === $categorieId) || (!$categorieId && !$this->corpsEstContractuel())) ? $diplome->salaire_brut : null),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // =========================
            // IDENTITÉ
            // =========================

            'matricule' => [
                'required',
                'string',
                'max:30',
                'unique:enseignants,matricule',
            ],

            'nom' => [
                'required',
                'string',
                'max:50',
            ],

            'prenom' => [
                'required',
                'string',
                'max:50',
            ],


            'date_naissance' => [
                'nullable',
                'date',
                'before:today',
            ],

            'lieu_naissance' => [
                'nullable',
                'string',
                'max:100',
            ],

            'genre' => [
                'nullable',
                Rule::in([
                    'M',
                    'F',
                    'masculin',
                    'feminin',
                ]),
            ],

            'telephone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:100',
            ],

            'adresse' => [
                'nullable',
                'string',
                'max:255',
            ],

            'cni' => ['nullable', 'string', 'max:50'],
            'diplome_id' => ['nullable', 'integer', 'exists:diplomes,id', function ($attribute, $value, $fail): void {
                $diplome = Diplome::find($value);
                if ($this->filled('categorie_id') && $diplome && (int) $diplome->categorie_id !== $this->integer('categorie_id')) {
                    $fail('Aucun salaire brut paramétré pour ce diplôme et cette catégorie.');
                }
            }],
            'lieu_service_id' => ['nullable', 'integer', 'exists:lieu_de_services,id'],
            'lieu_paiement_id' => ['nullable', 'integer'],
            'salaire_brut' => ['nullable', 'numeric', 'min:0'],
            'generation' => ['nullable', 'string', 'max:20'],
            'date_fin_contrat' => [Rule::requiredIf(fn (): bool => $this->corpsEstContractuel()), 'nullable', 'date', 'after_or_equal:date_recrutement'],
            'est_en_couple' => ['required', 'boolean'],
            'nombre_enfants' => ['nullable', 'integer', 'min:0'],
            'nombre_femmes' => ['nullable', 'integer', 'min:0'],
            'nombre_parts_fiscales' => ['required', 'numeric', 'min:1', 'max:5'],
            'conjoint_travaille' => ['required', 'boolean'],
            'observations' => ['nullable', 'string'],

            // =========================
            // ADMINISTRATION
            // =========================

            'date_recrutement' => [
                'nullable',
                'date',
            ],

            'date_prise_service' => [
                'nullable',
                'date',
            ],

            'ia_id' => [
                'required',
                'integer',
                'exists:ias,id',
            ],

            'ief_id' => [
                'required',
                'integer',
                'exists:iefs,id',
            ],

            'corps_id' => [
                'required',
                'integer',
                'exists:corps_enseignant,id',
            ],

            'categorie_id' => [
                Rule::requiredIf(fn (): bool => $this->corpsEstContractuel()),
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('corps_id', $this->integer('corps_id'))),
            ],

            'discipline_id' => [
                'nullable',
                'integer',
                'exists:disciplines,id',
            ],

            'statut_enseignant_id' => [
                'nullable',
                'integer',
                'exists:statuts_enseignant,id',
            ],

            'statut' => [
                'required',
                Rule::in([
                    'en_activite',
                    'retraite',
                    'suspension_provisoire',
                    'abandon',
                    'decede',
                    'integre',
                    'radie',
                    'cessation_paiement',
                ]),
            ],

            'est_actif' => [
                'required',
                'boolean',
            ],

            // =========================
            // COMPTE BANCAIRE
            // =========================

            'compte_bancaire' => [
                'nullable',
                'array',
            ],

            'compte_bancaire.institut_financier_id' => [
                'nullable',
                'integer',
                'exists:instituts_financieres,id',
            ],

            'compte_bancaire.code_banque' => [
                'nullable',
                'string',
                'max:5',
            ],

            'compte_bancaire.code_guichet' => [
                'nullable',
                'string',
                'max:5',
            ],

            'compte_bancaire.numero_compte' => [
                'nullable',
                'string',
                'max:11',
],

            'compte_bancaire.cle_rib' => [
                'nullable',
                'string',
                'max:2',
            ],

            'compte_bancaire.iban' => [
                'nullable',
                'string',
                'max:34',
            ],

            'compte_bancaire.bic' => [
                'nullable',
                'string',
                'max:11',
            ],

            'compte_bancaire.titulaire_compte' => [
                'nullable',
                'string',
                'max:100',
            ],

            'compte_bancaire.type_virement' => [
                'nullable',
                Rule::in([
                'unitaire',
                'masse',
                ]),
            ],

            

            'compte_bancaire.est_principal' => [
                'nullable',
                'boolean',
            ],

            // =========================
            // SYNDICAT
            // =========================

            'syndicat' => [
                'nullable',
                'array',
            ],

            'syndicat.syndicat_id' => [
                'nullable',
                'integer',
                'exists:syndicats,id',
            ],

            'syndicat.taux_personnalise' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'syndicat.date_adhesion' => [
                'nullable',
                'date',
            ],

            'syndicat.date_resiliation' => [
                'nullable',
                'date',
                'after_or_equal:syndicat.date_adhesion',
            ],

            'syndicat.numero_affiliation' => [
                'nullable',
                'string',
                'max:50',
            ],

            // =========================
            // MUTUELLE
            // =========================

            'mutuelle' => [
                'nullable',
                'array',
            ],

            'mutuelle.mutuelle_id' => [
                'nullable',
                'integer',
                'exists:mutuelles,id',
            ],

            'mutuelle.numero_affiliation' => [
                'nullable',
                'string',
                'max:50',
            ],

            'mutuelle.date_adhesion' => [
                'nullable',
                'date',
            ],

            'mutuelle.date_resiliation' => [
                'nullable',
                'date',
                'after_or_equal:mutuelle.date_adhesion',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_femmes.integer' => 'Le nombre de femmes doit être un nombre entier.',
            'nombre_femmes.min' => 'Le nombre de femmes ne peut pas être négatif.',
            'nombre_enfants.integer' => 'Le nombre d’enfants doit être un nombre entier.',
            'nombre_enfants.min' => 'Le nombre d’enfants ne peut pas être négatif.',
            'date_fin_contrat.required' => 'La date de fin du contrat est obligatoire pour un enseignant contractuel.',
            'date_fin_contrat.after_or_equal' => 'La fin du contrat doit être postérieure ou égale à la date de recrutement.',
            'diplome_id.exists' => 'Le diplôme sélectionné n’existe plus. Veuillez le sélectionner à nouveau.',
            'salaire_brut.min' => 'Le salaire brut ne peut pas être négatif.',
            'matricule.required' =>
                'Le matricule est obligatoire.',

            'matricule.unique' =>
                'Ce matricule existe déjà.',

            'nom.required' =>
                'Le nom est obligatoire.',

            'prenom.required' =>
                'Le prénom est obligatoire.',

            'corps_id.required' =>
                'Le corps est obligatoire.',

            'ia_id.required' =>
                'L’Inspection académique est obligatoire.',

            'ief_id.required' =>
                'L’Inspection de l’Éducation et de la Formation est obligatoire.',

            'date_naissance.before' =>
                'La date de naissance doit être antérieure à aujourd’hui.',

            'genre.in' =>
                'Le genre doit être M, F, masculin ou feminin.',

            'ia_id.exists' =>
                'L’IA sélectionnée n’existe pas.',

            'ief_id.exists' =>
                'L’IEF sélectionnée n’existe pas.',

            'corps_id.exists' =>
                'Le corps sélectionné n’existe pas.',

            'categorie_id.exists' =>
                'La catégorie sélectionnée n’appartient pas au corps choisi.',

            'categorie_id.required' =>
                'La catégorie est obligatoire pour le corps Contractuel.',

            'discipline_id.exists' =>
                'La discipline sélectionnée n’existe pas.',

            'statut_enseignant_id.exists' =>
                'Le statut enseignant sélectionné n’existe pas.',

            'compte_bancaire.institut_financier_id.exists' =>
                'L’institut financier sélectionné n’existe pas.',
            
            'compte_bancaire.numero_compte.max' =>
                'Le numéro de compte ne doit pas dépasser 11 caractères.',

            'syndicat.syndicat_id.exists' =>
                'Le syndicat sélectionné n’existe pas.',

            'mutuelle.mutuelle_id.exists' =>
                'La mutuelle sélectionnée n’existe pas.',
        ];
    }

    private function corpsEstContractuel(): bool
    {
        return CorpsEnseignant::query()
            ->whereKey($this->integer('corps_id'))
            ->where(function ($query): void {
                $query->whereRaw('LOWER(libelle) = ?', ['contractuel'])
                    ->orWhereRaw('LOWER(code) = ?', ['contractuel']);
            })
            ->exists();
    }

    private function corpsEstVacataire(): bool
    {
        return CorpsEnseignant::query()
            ->whereKey($this->integer('corps_id'))
            ->where(function ($query): void {
                $query->whereRaw('LOWER(libelle) LIKE ?', ['%vacat%'])
                    ->orWhereRaw('LOWER(code) = ?', ['vac'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%vacat%']);
            })
            ->exists();
    }
}
