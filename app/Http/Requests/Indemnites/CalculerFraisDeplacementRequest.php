<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class CalculerFraisDeplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.type_frais' => ['required', 'string', 'max:100'],
            'lignes.*.bareme_id' => ['nullable', 'integer', 'exists:baremes_deplacement,id'],
            'lignes.*.quantite' => ['required', 'numeric', 'min:0'],
            'lignes.*.taux_unitaire' => ['nullable', 'numeric', 'min:0'],
            'lignes.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
