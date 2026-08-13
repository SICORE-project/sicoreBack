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
            // dans CETTE convocation (President de jury, Surveillant/correcteur, ...),
            // sa categorie de personnel (fonctionnaire/contractuel/vacataire,
            // utile pour le calcul des indemnites), et peut etre rattache a
            // un centre d'examen precis de la convocation.
            'beneficiaires' => ['required_without:enseignant_ids', 'array', 'min:1'],
            'beneficiaires.*.enseignant_id' => ['required_with:beneficiaires', 'integer', 'exists:enseignants,id'],
            'beneficiaires.*.fonction' => ['nullable', 'string', 'max:100'],
            'beneficiaires.*.centre_id' => ['nullable', 'integer', 'exists:convocation_centres,id'],
            // Metier precis du centre (un centre peut en avoir plusieurs) —
            // voir ConvocationCentreMetier.
            'beneficiaires.*.centre_metier_id' => ['nullable', 'integer', 'exists:convocation_centre_metiers,id'],
            'beneficiaires.*.provenance' => ['nullable', 'string', 'max:255'],
            'beneficiaires.*.categorie_personnel' => ['nullable', 'in:fonctionnaire,contractuel,vacataire'],
        ];
    }
}
