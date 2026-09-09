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
        return [
            'libelle' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'libelle')->ignore($this->route('id')),
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
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.unique' => 'Une catégorie avec ce libellé existe déjà.',
            'ordre.integer' => 'L’ordre doit être un entier.',
            'corps_id.required' => 'Le corps enseignant est obligatoire.',
            'corps_id.exists' => 'Le corps enseignant sélectionné est invalide.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'libelle' => trim((string) $this->input('libelle')),
        ]);
    }
}
