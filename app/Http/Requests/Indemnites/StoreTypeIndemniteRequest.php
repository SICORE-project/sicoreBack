<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:150', 'unique:type_indemnites,libelle'],
            'description' => ['nullable', 'string'],
            'mode_calcul' => ['required', 'in:forfaitaire,horaire,kilometrique'],
            'montant_forfaitaire' => ['required_if:mode_calcul,forfaitaire', 'nullable', 'numeric', 'min:0'],
            'taux_horaire' => ['required_if:mode_calcul,horaire', 'nullable', 'numeric', 'min:0'],
            'taux_kilometrique' => ['required_if:mode_calcul,kilometrique', 'nullable', 'numeric', 'min:0'],
            'plafond' => ['nullable', 'numeric', 'min:0'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
