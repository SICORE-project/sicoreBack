<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class StoreConvocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_convocation_id' => ['nullable', 'integer', 'exists:types_convocation,id'],
            'date_emission' => ['required', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'objet' => ['required', 'string', 'max:255'],
            // Session d'examen (ex: "BFEM 2026") — colonne ajoutée pour la
            // liste DAGE du cahier des charges "Transmission des convocations".
            'session' => ['nullable', 'string', 'max:150'],
            'ordre_de_mission' => ['nullable', 'boolean'],
            'lieu_affectation' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', 'in:brouillon,emise,envoyee'],
            'utilisateur_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
