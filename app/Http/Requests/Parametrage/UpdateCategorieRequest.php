<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categorieId = $this->route('category');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'code')
                    ->ignore($categorieId),
            ],

            'libelle' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'ordre' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'corps_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:corps_enseignant,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code existe déjà.',
            'libelle.required' => 'Le libellé est obligatoire.',
            'ordre.integer' => 'L’ordre doit être un entier.',
            'corps_id.required' => 'Le corps enseignant est obligatoire.',
            'corps_id.exists' => 'Le corps enseignant sélectionné est invalide.',
        ];
    }
}