<?php

namespace App\Http\Requests\Parametrage\Specialite;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecialiteRequest extends FormRequest
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
                'max:20',
                Rule::unique('specialites', 'code')->ignore($id),
            ],

            'libelle' => [
                'required',
                'string',
                'max:100',
                Rule::unique('specialites', 'libelle')->ignore($id),
            ],

            'est_actif' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code de la spécialité est obligatoire.',
            'code.string' => 'Le code de la spécialité doit être une chaîne de caractères.',
            'code.max' => 'Le code de la spécialité ne doit pas dépasser 20 caractères.',
            'code.unique' => 'Ce code de spécialité existe déjà.',

            'libelle.required' => 'Le libellé de la spécialité est obligatoire.',
            'libelle.string' => 'Le libellé de la spécialité doit être une chaîne de caractères.',
            'libelle.max' => 'Le libellé de la spécialité ne doit pas dépasser 100 caractères.',
            'libelle.unique' => 'Ce libellé de spécialité existe déjà.',

            'est_actif.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }
}