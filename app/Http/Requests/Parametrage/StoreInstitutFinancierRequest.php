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
            'email.email' => 'L’adresse email doit être valide.',
        ];
    }
}
