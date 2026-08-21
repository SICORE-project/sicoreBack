<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Création d'une mission de déplacement (App\Models\MissionDeplacement).
 */
class StoreFraisDeplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'beneficiaire_id' => ['required', 'integer', 'exists:users,id'],
            'lieu_depart' => ['required', 'string', 'max:255'],
            'lieu_destination' => ['required', 'string', 'max:255'],
            'motif' => ['nullable', 'string', 'max:255'],
            'date_depart' => ['required', 'date'],
            'date_retour' => ['required', 'date', 'after_or_equal:date_depart'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'moyen_transport' => ['nullable', 'string', 'max:100'],
            'statut_agent' => ['nullable', 'string', 'max:100'],
            'indice_agent' => ['nullable', 'numeric', 'min:0'],
            'salaire_global_annuel' => ['nullable', 'numeric', 'min:0'],
            'lieu_service' => ['nullable', 'string', 'max:255'],
        ];
    }
}
