<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceFaitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'convocation_id' => ['nullable', 'integer', 'exists:convocations,id'],
            'enseignant_id' => ['sometimes', 'integer', 'exists:enseignants,id'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'date', 'after_or_equal:date_debut'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'nombre_jours' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
