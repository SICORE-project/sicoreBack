<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Création d'une demande d'aide étudiante (App\Models\DemandeAide).
 */
class StoreAideEtudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_aide_id' => ['required', 'integer', 'exists:types_aides,id'],
            'etudiant_id' => ['required', 'integer', 'exists:etudiants,id'],
            'utilisateur_id' => ['nullable', 'integer', 'exists:users,id'],
            'motif' => ['required', 'string', 'max:1000'],
        ];
    }
}
