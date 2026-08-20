<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceFaitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'convocation_id' => ['nullable', 'integer', 'exists:convocations,id'],
            'enseignant_id' => ['required', 'integer', 'exists:enseignants,id'],
            'utilisateur_id' => ['required', 'integer', 'exists:users,id'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'nombre_jours' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
