<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompteBancaireEnseignantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institut_financier_id' => [
                'required',
                'integer',
                Rule::exists('instituts_financieres', 'id')->where('est_actif', true),
            ],
            'numero_compte' => [
                'required',
                'string',
                'max:34',
                Rule::unique('comptes_bancaires_enseignants', 'numero_compte')
                    ->where('enseignant_id', $this->route('enseignant')),
            ],
            'rib' => ['required', 'string', 'max:34'],
            'est_actif' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'institut_financier_id.exists' => 'L’institution financière sélectionnée est inexistante ou inactive.',
            'numero_compte.unique' => 'Ce compte bancaire est déjà associé à cet enseignant.',
        ];
    }
}
