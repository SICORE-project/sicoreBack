<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEtatPaieIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode_debut' => ['sometimes', 'date'],
            'periode_fin' => ['sometimes', 'date', 'after_or_equal:periode_debut'],
            'lieu_examen' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:100'],
            'commentaire_correction' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
