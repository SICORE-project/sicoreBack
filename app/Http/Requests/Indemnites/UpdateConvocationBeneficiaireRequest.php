<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Modification d'UN beneficiaire deja rattache a une convocation (fiche
 * "Modifier" — section Membres du jury). Memes champs que le format
 * "nouveau" de AttachBeneficiairesConvocationRequest, sur un seul
 * beneficiaire identifie par son enseignant_id dans l'URL.
 */
class UpdateConvocationBeneficiaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fonction' => ['nullable', 'string', 'max:100'],
            'provenance' => ['nullable', 'string', 'max:255'],
            'centre_id' => ['nullable', 'integer', 'exists:convocation_centres,id'],
            // Metier precis du centre (un centre peut en avoir plusieurs) —
            // voir ConvocationCentreMetier.
            'centre_metier_id' => ['nullable', 'integer', 'exists:convocation_centre_metiers,id'],
            'categorie_personnel' => ['nullable', 'in:fonctionnaire,contractuel,vacataire'],
        ];
    }
}
