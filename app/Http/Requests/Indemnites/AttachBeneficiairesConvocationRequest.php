<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class AttachBeneficiairesConvocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Ancien format (conserve pour compatibilite) : liste brute d'IDs.
            'enseignant_ids' => ['required_without:beneficiaires', 'array', 'min:1'],
            'enseignant_ids.*' => ['integer', 'exists:enseignants,id'],

            // Nouveau format : chaque beneficiaire porte sa propre fonction
            // dans CETTE convocation (President de jury, Surveillant/correcteur, ...).
            'beneficiaires' => ['required_without:enseignant_ids', 'array', 'min:1'],
            'beneficiaires.*.enseignant_id' => ['required_with:beneficiaires', 'integer', 'exists:enseignants,id'],
            'beneficiaires.*.fonction' => ['nullable', 'string', 'max:100'],
        ];
    }
}
