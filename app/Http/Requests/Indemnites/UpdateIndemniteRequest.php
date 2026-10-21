<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'utilisateur_id' => ['sometimes', 'integer', 'exists:users,id'],
            'type_indemnite_id' => ['sometimes', 'integer', 'exists:type_indemnites,id'],
            'bareme_id' => ['nullable', 'integer'],
            'montant' => ['nullable', 'numeric', 'min:0'],
            'montant_base' => ['nullable', 'numeric', 'min:0'],
            'frais_deplacement' => ['nullable', 'numeric', 'min:0'],
            'montant_total' => ['nullable', 'numeric', 'min:0'],
            'statut' => ['nullable', 'in:brouillon,calcule,valide,rejete'],
            'nombre_copies' => ['nullable', 'integer', 'min:0'],
            'ordre_de_mission' => ['nullable', 'boolean'],
            'lieu_affectation' => ['nullable', 'string', 'max:255'],
            'indice' => ['nullable', 'numeric', 'min:0'],
            'nombre_heures' => ['nullable', 'numeric', 'min:0'],
            'nombre_kilometrages' => ['nullable', 'numeric', 'min:0'],
            'commentaire_validation' => ['nullable', 'string'],
        ];
    }
}
