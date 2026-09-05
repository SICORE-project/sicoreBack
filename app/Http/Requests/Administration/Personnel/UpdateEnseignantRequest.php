<?php

namespace App\Http\Requests\Administration\Personnel;

use App\Models\Parametrage\CorpsEnseignant;
use App\Models\Parametrage\Diplome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnseignantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
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
        $enseignantId = $this->route('id');

        return [
            'matricule' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('enseignants', 'matricule')->ignore($enseignantId)],
            'nom' => ['sometimes', 'required', 'string', 'max:50'],
            'prenom' => ['sometimes', 'required', 'string', 'max:50'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', Rule::in(['M', 'F', 'masculin', 'feminin'])],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'cni' => ['nullable', 'string', 'max:50'],
            'diplome_id' => ['nullable', 'integer', 'exists:diplomes,id', function ($attribute, $value, $fail): void {
                $diplome = Diplome::find($value);
                if ($this->filled('categorie_id') && $diplome && (int) $diplome->categorie_id !== $this->integer('categorie_id')) {
                    $fail('Aucun salaire brut paramétré pour ce diplôme et cette catégorie.');
                }
            }],
            'lieu_service_id' => ['nullable', 'integer', 'exists:lieu_de_services,id'],
            'salaire_brut' => ['nullable', 'numeric', 'min:0'],
            'generation' => ['nullable', 'string', 'max:20'],
            'date_fin_contrat' => [Rule::requiredIf(fn (): bool => $this->corpsEstContractuel()), 'nullable', 'date', 'after_or_equal:date_recrutement'],
            'est_en_couple' => ['required', 'boolean'],
            'nombre_enfants' => ['nullable', 'integer', 'min:0'],
            'nombre_femmes' => ['nullable', 'integer', 'min:0'],
            'nombre_parts_fiscales' => ['required', 'numeric', 'min:1', 'max:5'],
            'conjoint_travaille' => ['required', 'boolean'],
            'observations' => ['nullable', 'string'],
            'compte_bancaire' => ['nullable', 'array'],
            'compte_bancaire.institut_financier_id' => ['nullable', 'integer', 'exists:instituts_financieres,id'],
            'compte_bancaire.code_banque' => ['nullable', 'string', 'max:5'],
            'compte_bancaire.code_guichet' => ['nullable', 'string', 'max:5'],
            'compte_bancaire.numero_compte' => ['nullable', 'string', 'max:11'],
            'compte_bancaire.cle_rib' => ['nullable', 'string', 'max:2'],
            'compte_bancaire.iban' => ['nullable', 'string', 'max:34'],
            'compte_bancaire.bic' => ['nullable', 'string', 'max:11'],
            'compte_bancaire.titulaire_compte' => ['nullable', 'string', 'max:100'],
            'compte_bancaire.type_virement' => ['nullable', Rule::in(['unitaire', 'masse'])],
            'date_recrutement' => ['nullable', 'date'],
            'date_prise_service' => ['nullable', 'date'],
            'ia_id' => ['sometimes', 'required', 'integer', 'exists:ias,id'],
            'ief_id' => ['sometimes', 'required', 'integer', 'exists:iefs,id'],
            'corps_id' => ['sometimes', 'required', 'integer', 'exists:corps_enseignant,id'],
            'categorie_id' => [Rule::requiredIf(fn (): bool => $this->corpsEstContractuel()), 'nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('corps_id', $this->integer('corps_id')))],
            'discipline_id' => ['nullable', 'integer', 'exists:disciplines,id'],
            'statut_enseignant_id' => ['nullable', 'integer', 'exists:statuts_enseignant,id'],
            'statut' => ['sometimes', 'required', Rule::in(['en_activite', 'retraite', 'suspension_provisoire', 'abandon', 'decede', 'integre', 'radie', 'cessation_paiement'])],
            'est_actif' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'matricule.required' => 'Le matricule est obligatoire.',
            'matricule.unique' => 'Ce matricule existe déjà.',
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'corps_id.required' => 'Le corps est obligatoire.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd’hui.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'ia_id.exists' => 'L’IA sélectionnée n’existe pas.',
            'ief_id.exists' => 'L’IEF sélectionnée n’existe pas.',
            'corps_id.exists' => 'Le corps sélectionné n’existe pas.',
            'categorie_id.exists' => 'La catégorie sélectionnée n’appartient pas au corps choisi.',
            'categorie_id.required' => 'La catégorie est obligatoire pour le corps Contractuel.',
            'discipline_id.exists' => 'La discipline sélectionnée n’existe pas.',
            'statut.in' => 'Le statut sélectionné est invalide.',
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
