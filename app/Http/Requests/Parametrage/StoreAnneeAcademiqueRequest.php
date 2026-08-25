<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnneeAcademiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'libelle' => is_string($this->libelle) ? trim($this->libelle) : $this->libelle,
            'observations' => is_string($this->observations) ? trim($this->observations) : $this->observations,
        ]);
    }

    public function rules(): array
    {
        return [
            'libelle' => [
                'required',
                'string',
                'max:100',
                'unique:annee_academiques,libelle',
            ],

            'date_debut' => [
                'required',
                'date',
            ],

            'date_fin' => [
                'required',
                'date',
                'after:date_debut',
            ],

            'observations' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.unique' => 'Cette année académique existe déjà.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}
