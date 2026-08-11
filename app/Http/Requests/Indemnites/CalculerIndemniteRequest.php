<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Utilisée pour /indemnites/calculer (persiste le résultat)
 * et /indemnites/simuler (ne persiste pas), les deux actions
 * partageant les mêmes données d'entrée.
 */
class CalculerIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'utilisateur_id' => ['required', 'integer', 'exists:users,id'],
            'type_indemnite_id' => ['required', 'integer', 'exists:type_indemnites,id'],
            'nombre_heures' => ['nullable', 'numeric', 'min:0'],
            'nombre_kilometrages' => ['nullable', 'numeric', 'min:0'],
            'nombre_copies' => ['nullable', 'integer', 'min:0'],
            'indice' => ['nullable', 'numeric', 'min:0'],
            'ordre_de_mission' => ['nullable', 'boolean'],
            'lieu_affectation' => ['nullable', 'string', 'max:255'],
            'frais_deplacement' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
