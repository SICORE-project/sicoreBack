<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Utilisée pour /indemnites/calculer (persiste le résultat)
 * et /indemnites/simuler (ne persiste pas), les deux actions
 * partageant les mêmes données d'entrée.
 *
 * MODIFIÉ : `type_indemnite_id` devient optionnel. Si absent, le
 * contrôleur le résout automatiquement à partir de `convocation_id`
 * (→ type_convocation) et `enseignant_id` (→ categorie_personnel).
 * Il faut donc fournir soit `type_indemnite_id`, soit `convocation_id`.
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

            'type_indemnite_id' => ['nullable', 'required_without:convocation_id', 'integer', 'exists:type_indemnites,id'],
            'convocation_id' => ['nullable', 'required_without:type_indemnite_id', 'integer', 'exists:convocations,id'],
            'enseignant_id' => ['nullable', 'integer', 'exists:enseignants,id'],

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
