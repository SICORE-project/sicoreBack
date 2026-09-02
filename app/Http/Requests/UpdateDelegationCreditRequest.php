<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDelegationCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => 'sometimes|string|max:50|unique:delegation_credits,reference,' . $this->route('delegation_credit'),
            'objet' => 'sometimes|string|max:255',
            'annee_academique' => 'sometimes|string|max:20',
            'periode_paie' => 'nullable|string|max:50',
            'montant_initial' => 'sometimes|numeric|min:0',
            'montant_disponible' => 'sometimes|numeric|min:0',
            'date_delegation' => 'sometimes|date',
            'date_fin' => 'nullable|date|after_or_equal:date_delegation',
            'statut' => 'sometimes|string|in:En attente,Validée,Rejetée',
            // structure_id et service_id sont nullable en base depuis le passage
            // a l'axe corps / IA / IEF : une delegation au format FINPRONET n'en
            // a pas. Sans 'nullable', 'exists' rejette la valeur null et rend
            // ces delegations impossibles a modifier.
            'structure_id' => 'sometimes|nullable|exists:structures,id',
            'service_id' => 'sometimes|nullable|exists:services,id',
        ];
    }

    public function messages(): array
    {
        return [
            'montant_disponible.min' => 'Le montant doit être positif.',
            'montant_initial.min' => 'Le montant initial doit être positif.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de délégation.',
            'reference.unique' => 'Cette référence existe déjà.',
        ];
    }
}
