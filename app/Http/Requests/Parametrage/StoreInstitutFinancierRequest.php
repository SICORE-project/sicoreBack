<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutFinancierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'     => ['required', 'string', 'max:150'],
            'sigle'       => ['nullable', 'string', 'max:30'],
            'code_banque' => ['nullable', 'string', 'max:10', 'unique:instituts_financieres,code_banque'],
            
            // Validation des guichets
            'guichets'           => ['nullable', 'array'],
            'guichets.*.code'    => ['required', 'string', 'max:10', 'distinct'],
            'guichets.*.libelle' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code_banque.unique'       => 'Ce code banque est déjà utilisé par une autre institution.',
            'guichets.*.code.distinct' => 'Les codes des guichets doivent être différents les uns des autres.',
            'guichets.*.code.required' => 'Le code du guichet est obligatoire.',
            'guichets.*.libelle.required' => 'Le libellé du guichet est obligatoire.',
        ];
    }
}