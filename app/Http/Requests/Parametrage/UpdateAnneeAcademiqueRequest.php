<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnneeAcademiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('libelle')) {
            $data['libelle'] = is_string($this->libelle) ? trim($this->libelle) : $this->libelle;
        }

        if ($this->has('observations')) {
            $data['observations'] = is_string($this->observations) ? trim($this->observations) : $this->observations;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $id = $this->route('annees_academique')
            ?? $this->route('id');

        return [
            'libelle' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('annee_academiques', 'libelle')->ignore($id),
            ],

            'date_debut' => [
                'sometimes',
                'required',
                'date',
            ],

            'date_fin' => [
                'sometimes',
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
}
