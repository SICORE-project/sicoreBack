<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRubriquePaieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? Str::upper(trim($this->code)) : $this->code,
            'libelle' => is_string($this->libelle) ? trim($this->libelle) : $this->libelle,
            'formule_calcul' => is_string($this->formule_calcul) ? trim($this->formule_calcul) : $this->formule_calcul,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/', Rule::unique('rubrique_paies', 'code')],
            'libelle' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['gain', 'retenue'])],
            'periodicite' => ['required', Rule::in(['mensuelle', 'ponctuelle', 'annuelle'])],
            'est_cotisable' => ['required', 'boolean'],
            'est_imposable' => ['required', 'boolean'],
            'est_afficher_bulletin' => ['required', 'boolean'],
            'taux_defaut' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'montant_defaut' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99', 'decimal:0,2'],
            'formule_calcul' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'est_actif' => ['required', 'boolean'],
        ];
    }
}
