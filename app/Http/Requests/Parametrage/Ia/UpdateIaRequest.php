<?php

namespace App\Http\Requests\Parametrage\Ia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ias', 'code')->ignore($id),
            ],

            'libelle' => [
                'required',
                'string',
                'max:200',
            ],

            'region_id' => [
                'required',
                'integer',
                'exists:regions,id',
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code de l’IA est obligatoire.',
            'code.string' => 'Le code de l’IA doit être une chaîne de caractères.',
            'code.max' => 'Le code de l’IA ne doit pas dépasser 50 caractères.',
            'code.unique' => 'Ce code IA est déjà utilisé.',

            'libelle.required' => 'Le libellé de l’IA est obligatoire.',
            'libelle.string' => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 200 caractères.',

            'region_id.required' => 'La région est obligatoire.',
            'region_id.integer' => 'La région sélectionnée est invalide.',
            'region_id.exists' => 'La région sélectionnée n’existe pas.',

        ];
    }
}
