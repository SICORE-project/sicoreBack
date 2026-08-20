<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class StoreEtatPaieIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'beneficiaire_id' => ['nullable', 'integer', 'exists:users,id'],
            'periode_debut' => ['required', 'date'],
            'periode_fin' => ['required', 'date', 'after_or_equal:periode_debut'],
            'lieu_examen' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:100'],
            'perimetre' => ['nullable', 'array'],
        ];
    }
}
