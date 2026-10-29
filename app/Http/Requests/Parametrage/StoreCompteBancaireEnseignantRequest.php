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
            ],
            'rib' => ['required', 'string', 'size:24', Rule::unique('comptes_bancaires_enseignants', 'rib')],
            'est_actif' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'institut_financier_id.exists' => 'La banque sélectionnée est inexistante ou inactive.',
            'rib.size' => 'Le RIB complet doit comporter exactement 24 caractères.',
            'rib.unique' => 'Ce RIB complet est déjà associé à un autre enseignant.',
        ];
    }
}
