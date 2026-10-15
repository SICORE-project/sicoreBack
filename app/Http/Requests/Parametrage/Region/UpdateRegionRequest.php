<?php

namespace App\Http\Requests\Parametrage\Region;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends FormRequest
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

        if ($this->has('chef_lieu')) {
            $data['chef_lieu'] = $this->filled('chef_lieu')
                ? trim($this->chef_lieu)
                : null;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $regionId = $this->route('id');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::unique('regions', 'code')->ignore($regionId),
            ],

            'libelle' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('regions', 'libelle')->ignore($regionId),
            ],

            'chef_lieu' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'superficie' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'population' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code de la région est obligatoire.',
            'code.string' => 'Le code de la région doit être une chaîne de caractères.',
            'code.max' => 'Le code de la région ne doit pas dépasser 10 caractères.',
            'code.unique' => 'Ce code de région existe déjà.',

            'libelle.required' => 'Le libellé de la région est obligatoire.',
            'libelle.string' => 'Le libellé de la région doit être une chaîne de caractères.',
            'libelle.max' => 'Le libellé de la région ne doit pas dépasser 50 caractères.',
            'libelle.unique' => 'Cette région existe déjà.',

            'chef_lieu.string' => 'Le chef-lieu doit être une chaîne de caractères.',
            'chef_lieu.max' => 'Le chef-lieu ne doit pas dépasser 50 caractères.',

            'superficie.numeric' => 'La superficie doit être un nombre.',
            'superficie.min' => 'La superficie ne peut pas être négative.',

            'population.integer' => 'La population doit être un nombre entier.',
            'population.min' => 'La population ne peut pas être négative.',
        ];
    }
}