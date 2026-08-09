<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class GenererEtatPaieIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'perimetre' => ['nullable', 'array'],
            'perimetre.utilisateur_ids' => ['nullable', 'array'],
            'perimetre.utilisateur_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
