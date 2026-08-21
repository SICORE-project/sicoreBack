<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDelegationCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => 'required|string|max:50|unique:delegation_credits,reference',
            'objet' => 'required|string|max:255',
            'annee_academique' => 'required|string|max:20',
            'periode_paie' => 'nullable|string|max:50',
            'montant_initial' => 'nullable|numeric|min:0',
            'montant_disponible' => 'required|numeric|min:0',
            'date_delegation' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_delegation',
            'statut' => 'nullable|string|in:En attente,Validée,Rejetée',
            'structure_id' => 'nullable|exists:structures,id',
            'service_id' => 'nullable|exists:services,id',
        ];
    }

    public function messages(): array
    {
        return [
            'montant_disponible.min' => 'Le montant doit être positif.',
            'montant_initial.min' => 'Le montant initial doit être positif.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de délégation.',
            'reference.unique' => 'Cette référence existe déjà.',
            'structure_id.exists' => 'La structure sélectionnée est invalide.',
            'service_id.exists' => 'Le service sélectionné est invalide.',
        ];
    }
}
