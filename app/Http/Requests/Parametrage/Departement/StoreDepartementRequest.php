<?php

namespace App\Http\Requests\Parametrage\Departement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code')
                ? mb_strtoupper(trim($this->code))
                : null,

            'libelle' => $this->filled('libelle')
                ? trim($this->libelle)
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('departements', 'code'),
            ],

            'libelle' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departements', 'libelle'),
            ],

            'region_id' => [
                'required',
                'integer',
                Rule::exists('regions', 'id')
                    ->where(fn ($query) => $query->where('est_actif', true)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' =>
                'Le code du département est obligatoire.',

            'code.string' =>
                'Le code du département doit être une chaîne de caractères.',

            'code.max' =>
                'Le code du département ne doit pas dépasser 20 caractères.',

            'code.unique' =>
                'Ce code de département existe déjà.',


            'libelle.required' =>
                'Le libellé du département est obligatoire.',

            'libelle.string' =>
                'Le libellé du département doit être une chaîne de caractères.',

            'libelle.max' =>
                'Le libellé du département ne doit pas dépasser 100 caractères.',

            'libelle.unique' =>
                'Ce département existe déjà.',


            'region_id.required' =>
                'La région est obligatoire.',

            'region_id.integer' =>
                'La région sélectionnée est invalide.',

            'region_id.exists' =>
                'La région sélectionnée est inexistante ou inactive.',
        ];
    }
}