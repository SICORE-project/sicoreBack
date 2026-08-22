<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:categories,code',
            ],

            'libelle' => [
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