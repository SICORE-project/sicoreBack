<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AffecterEnseignantLieuServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lieu_service_id' => [
                'required',
                'integer',
                Rule::exists('lieu_de_services', 'id')->where(fn ($query) => $query
                    ->where('est_actif', true)
                    ->whereNull('deleted_at')),
            ],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut', 'after_or_equal:today'],
            'type' => ['sometimes', Rule::in(['affectation', 'reaffectation', 'detachement', 'mutation'])],
            'motif' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'lieu_service_id.exists' => 'Le lieu de service sélectionné est introuvable ou inactif.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
