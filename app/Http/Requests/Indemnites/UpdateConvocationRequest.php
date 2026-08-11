<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConvocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_emission' => ['sometimes', 'date'],
            'objet' => ['sometimes', 'string', 'max:255'],
            'lieu_examen' => ['nullable', 'string', 'max:255'],
            'ordre_de_mission' => ['nullable', 'boolean'],
            'lieu_affectation' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', 'in:brouillon,emise,envoyee,cloturee'],
            'utilisateur_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
