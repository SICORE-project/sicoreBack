<?php

namespace App\Http\Requests\Parametrage\Departement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('code')) {
            $data['code'] = $this->filled('code')
                ? mb_strtoupper(trim($this->code))
                : null;
        }

        if ($this->has('libelle')) {
            $data['libelle'] = $this->filled('libelle')
                ? trim($this->libelle)
                : null;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $departementId = $this->route('id');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('departements', 'code')
                    ->ignore($departementId),
            ],

            'libelle' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('departements', 'libelle')
                    ->ignore($departementId),
            ],

            'region_id' => [
                'sometimes',
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