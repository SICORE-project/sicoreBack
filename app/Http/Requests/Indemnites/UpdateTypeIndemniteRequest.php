<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypeIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('type_indemnite') ?? $this->route('id');

        return [
            'libelle' => ['sometimes', 'string', 'max:150', 'unique:type_indemnites,libelle,' . $id],
            'description' => ['nullable', 'string'],
            'mode_calcul' => ['sometimes', 'in:forfaitaire,horaire,kilometrique'],
            'montant_forfaitaire' => ['nullable', 'numeric', 'min:0'],
            'taux_horaire' => ['nullable', 'numeric', 'min:0'],
            'taux_kilometrique' => ['nullable', 'numeric', 'min:0'],
            'plafond' => ['nullable', 'numeric', 'min:0'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
