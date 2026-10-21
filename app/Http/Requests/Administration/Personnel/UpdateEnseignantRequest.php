<?php

namespace App\Http\Requests\Administration\Personnel;

use App\Http\Requests\Administration\Personnel\Concerns\ValidatesTeacherHierarchy;
use App\Http\Requests\Administration\Personnel\Concerns\ValidatesTeacherIdentity;
use App\Models\Parametrage\CorpsEnseignant;
use App\Models\Parametrage\Diplome;
use App\Models\Personnel\Enseignant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnseignantRequest extends FormRequest
{
    use ValidatesTeacherHierarchy, ValidatesTeacherIdentity;

    protected function prepareForValidation(): void
    {
        if ($this->route('id')) {
            $teacher = Enseignant::query()->find($this->route('id'));
            if ($teacher) {
                $this->mergeIfMissing($teacher->only(['statut_enseignant_id', 'situation_matrimoniale',
                    'est_en_couple', 'nombre_enfants', 'nombre_femmes', 'conjoint_travaille', 'statut']));
            }
        }
        $this->prepareTeacherIdentity(true);
        $this->prepareTeacherSituation();
        $hierarchyFields = ['ia_id', 'ief_id', 'lieu_service_id'];
        if ($this->route('id') && $this->hasAny($hierarchyFields)) {
            $enseignant = Enseignant::query()->find($this->route('id'), $hierarchyFields);
            if ($enseignant) {
                $this->mergeIfMissing($enseignant->getAttributes());
            }
        }

        $contractFields = ['corps_id', 'diplome_id', 'categorie_id', 'date_recrutement', 'date_fin_contrat'];
        if ($this->route('id') && ! $this->has($contractFields)) {
            $enseignant = Enseignant::query()->find($this->route('id'), $contractFields);
            if ($enseignant) {
                $defaults = $enseignant->getAttributes();
                if ($this->has('corps_id') && $this->integer('corps_id') !== (int) $enseignant->corps_id) {
                    $defaults['categorie_id'] = null;
                }
                $this->mergeIfMissing($defaults);
            }
        }

        if ($this->has('nombre_femmes') && ! $this->filled('nombre_femmes')) {
            $this->merge(['nombre_femmes' => 0]);
        }
        if ($this->has('compte_bancaire.type_virement') && ! $this->filled('compte_bancaire.type_virement')) {
            $this->merge(['compte_bancaire' => array_merge($this->input('compte_bancaire', []), ['type_virement' => 'unitaire'])]);
        }
        $marie = $this->boolean('est_en_couple');
        $enfants = max(0, $this->integer('nombre_enfants'));
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
            'nombre_femmes' => $marie ? ($this->input('nombre_femmes') ?? 0) : 0,
            'conjoint_travaille' => $conjointTravaille,
            'nombre_parts_fiscales' => min(5, max(1, 2 + ($enfants * .5) - ($conjointTravaille ? .5 : 0))),
            'salaire_brut' => $this->corpsEstVacataire() ? 150000 : ($diplome && (($categorieId && (int) $diplome->categorie_id === $categorieId) || (!$categorieId && !$this->corpsEstContractuel())) ? $diplome->salaire_brut : null),
        ]);
        $this->clearInapplicableTeacherFields();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->teacherIdentityRules(true),

            'nom' => ['sometimes', 'required', 'string', 'max:50', "regex:/\A\p{L}[\p{L}\p{M} ’'-]*\z/u"],
            'prenom' => ['sometimes', 'required', 'string', 'max:50', "regex:/\A\p{L}[\p{L}\p{M} ’'-]*\z/u"],
            'date_naissance' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->format('Y-m-d')],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', Rule::in(['M', 'F', 'masculin', 'feminin'])],
            'telephone' => ['sometimes', 'required', 'string', 'max:20', "regex:/\A\+?[0-9][0-9 ()-]*\z/"],
            'email' => ['nullable', 'email', 'max:100'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'cni' => ['sometimes', 'required', 'string', 'regex:/\A[0-9]{13,15}\z/'],
            'diplome_id' => ['nullable', 'integer', 'exists:diplomes,id', function ($attribute, $value, $fail): void {
                $diplome = Diplome::find($value);
                if ($this->filled('categorie_id') && $diplome && (int) $diplome->categorie_id !== $this->integer('categorie_id')) {
                    $fail('Aucun salaire brut paramétré pour ce diplôme et cette catégorie.');
                }
            }],
            'lieu_service_id' => ['nullable', 'integer', Rule::exists('lieu_de_services', 'id')->whereNull('deleted_at')],
            'salaire_brut' => ['nullable', 'numeric', 'min:0'],
            'annee_recrutement' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'situation_matrimoniale' => [Rule::requiredIf(fn () => ! $this->teacherIsFonctionnaire()), 'nullable', Rule::in(['celibataire', 'marie', 'veuf', 'divorce'])],
            'date_fin_contrat' => ['nullable', 'date', 'after_or_equal:date_recrutement'],
            'est_en_couple' => ['nullable', 'boolean'],
            'nombre_enfants' => [Rule::requiredIf(fn () => ! $this->teacherIsFonctionnaire()), 'nullable', 'integer', 'min:0'],
            'nombre_femmes' => ['nullable', 'integer', 'min:0'],
            'nombre_parts_fiscales' => ['required', 'numeric', 'min:1', 'max:5'],
            'conjoint_travaille' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string'],
            'compte_bancaire' => ['nullable', 'array'],
            'compte_bancaire.institut_financier_id' => ['nullable', 'integer', 'exists:instituts_financieres,id'],
            'compte_bancaire.code_banque' => ['required_with:compte_bancaire', 'string', 'max:5'],
            'compte_bancaire.code_guichet' => ['required_with:compte_bancaire', 'string', 'max:5', "regex:/\A[0-9]+\z/"],
            'compte_bancaire.numero_compte' => ['required_with:compte_bancaire', 'string', 'max:11'],
            'compte_bancaire.cle_rib' => ['required_with:compte_bancaire', 'string', 'max:2', "regex:/\A[0-9]+\z/"],
            'compte_bancaire.iban' => ['nullable', 'string', 'max:34'],
            'compte_bancaire.bic' => ['nullable', 'string', 'max:11'],
            'compte_bancaire.titulaire_compte' => ['nullable', 'string', 'max:100'],
            'compte_bancaire.type_virement' => ['nullable', Rule::in(['unitaire', 'masse'])],
            'date_recrutement' => ['nullable', 'date'],
            'date_prise_service' => ['nullable', 'date'],
            'ia_id' => ['sometimes', 'required', 'integer', Rule::exists('ias', 'id')->whereNull('deleted_at')],
            'ief_id' => ['sometimes', 'required', 'integer', Rule::exists('iefs', 'id')->whereNull('deleted_at')],
            'corps_id' => [Rule::requiredIf(fn () => $this->teacherIsFonctionnaire()), 'nullable', 'integer', 'exists:corps_enseignant,id'],
            'categorie_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('corps_id', $this->integer('corps_id')))],
            'discipline_id' => ['nullable', 'integer', 'exists:disciplines,id'],
            'statut_enseignant_id' => ['required', 'integer', 'exists:statuts_enseignant,id'],
            'statut' => ['nullable', Rule::in(['en_activite', 'retraite', 'suspension_provisoire', 'abandon', 'decede', 'integre', 'radie', 'cessation_paiement'])],
            'est_actif' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->teacherIdentityMessages(),
            'nombre_femmes.integer' => 'Le nombre de femmes doit être un nombre entier.',
            'nombre_femmes.min' => 'Le nombre de femmes ne peut pas être négatif.',
            'nombre_enfants.integer' => 'Le nombre d’enfants doit être un nombre entier.',
            'nombre_enfants.min' => 'Le nombre d’enfants ne peut pas être négatif.',
            'date_fin_contrat.required' => 'La date de fin du contrat est obligatoire pour un enseignant contractuel.',
            'date_fin_contrat.after_or_equal' => 'La fin du contrat doit être postérieure ou égale à la date de recrutement.',
            'diplome_id.exists' => 'Le diplôme sélectionné n’existe plus. Veuillez le sélectionner à nouveau.',
            'salaire_brut.min' => 'Le salaire brut ne peut pas être négatif.',
            'cni.regex' => 'Le numéro de carte d’identité doit contenir entre 13 et 15 chiffres.',
            'matricule.required' => 'Le matricule est obligatoire.',
            'matricule.unique' => 'Ce matricule existe déjà.',
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'corps_id.required' => 'Le corps est obligatoire.',
            'date_naissance.before_or_equal' => 'L’enseignant doit avoir au moins 18 ans.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'ia_id.exists' => 'L’IA sélectionnée n’existe pas.',
            'ief_id.exists' => 'L’IEF sélectionnée n’existe pas.',
            'corps_id.exists' => 'Le corps sélectionné n’existe pas.',
            'categorie_id.exists' => 'La catégorie sélectionnée n’appartient pas au corps choisi.',
            'categorie_id.required' => 'La catégorie est obligatoire pour le corps Contractuel.',
            'discipline_id.exists' => 'La spécialité sélectionnée n’existe pas.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ];
    }

    private function corpsEstContractuel(): bool
    {
        return \App\Models\Parametrage\StatutEnseignant::query()
            ->whereKey($this->integer('statut_enseignant_id'))
            ->where(function ($query): void {
                $query->whereRaw('LOWER(libelle) = ?', ['contractuel'])
                    ->orWhereRaw('LOWER(code) = ?', ['contractuel']);
            })
            ->exists();
    }

    private function corpsEstVacataire(): bool
    {
        return \App\Models\Parametrage\StatutEnseignant::query()
            ->whereKey($this->integer('statut_enseignant_id'))
            ->where(function ($query): void {
                $query->whereRaw('LOWER(libelle) LIKE ?', ['%vacat%'])
                    ->orWhereRaw('LOWER(code) = ?', ['vac'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%vacat%']);
            })
            ->exists();
    }
}
