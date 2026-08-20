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

            'adresse' => [
                'nullable',
                'string',
                'max:255',
            ],

            'telephone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:100',
            ],

            'responsable' => [
                'nullable',
                'string',
                'max:100',
            ],
            'est_actif' => [
                'nullable',
                'boolean',
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

            'adresse.string' => 'L’adresse doit être une chaîne de caractères.',
            'adresse.max' => 'L’adresse ne doit pas dépasser 255 caractères.',

            'telephone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'telephone.max' => 'Le numéro de téléphone ne doit pas dépasser 20 caractères.',

            'email.email' => 'L’adresse email n’est pas valide.',
            'email.max' => 'L’adresse email ne doit pas dépasser 100 caractères.',

            'responsable.string' => 'Le responsable doit être une chaîne de caractères.',
            'responsable.max' => 'Le nom du responsable ne doit pas dépasser 100 caractères.',
        ];
    }
}