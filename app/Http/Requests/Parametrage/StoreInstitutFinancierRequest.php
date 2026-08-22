<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstitutFinancierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('instituts_financieres', 'code')],
            'libelle' => ['required', 'string', 'max:150'],
            'sigle' => ['nullable', 'string', 'max:30'],
            'type_institution' => ['required', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email:rfc', 'max:100'],
            'est_actif' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code est déjà utilisé par une institution financière.',
            'email.email' => 'L’adresse email doit être valide.',
        ];
    }
}
