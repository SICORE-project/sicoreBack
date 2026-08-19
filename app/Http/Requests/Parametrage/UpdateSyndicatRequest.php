<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Parametrage\Syndicat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateSyndicatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('code')) {
            $data['code'] = is_string($this->code) ? Str::upper(trim($this->code)) : $this->code;
        }

        if ($this->has('libelle')) {
            $data['libelle'] = is_string($this->libelle) ? trim($this->libelle) : $this->libelle;
        }

        foreach (['montant_check_off', 'montant_oeuvre_sociale'] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $data[$field] = is_string($value) ? str_replace(',', '.', trim($value)) : $value;
            }
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $syndicat = $this->route('syndicat') ?? $this->route('id');
        $syndicatId = $syndicat instanceof Syndicat ? $syndicat->getKey() : $syndicat;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('syndicats', 'code')->ignore($syndicatId),
            ],
            'libelle' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('syndicats', 'libelle')->ignore($syndicatId),
            ],
            'montant_check_off' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999.99'],
            'montant_oeuvre_sociale' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999.99'],
            'est_actif' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code du syndicat est obligatoire.',
            'code.max' => 'Le code du syndicat ne doit pas dépasser 20 caractères.',
            'code.unique' => 'Ce code de syndicat existe déjà.',
            'libelle.required' => 'Le libellé du syndicat est obligatoire.',
            'libelle.max' => 'Le libellé du syndicat ne doit pas dépasser 100 caractères.',
            'libelle.unique' => 'Ce libellé de syndicat existe déjà.',
            'montant_check_off.numeric' => 'Le montant du check-off doit être un nombre.',
            'montant_check_off.min' => 'Le montant du check-off ne peut pas être négatif.',
            'montant_check_off.decimal' => 'Le montant du check-off ne doit pas avoir plus de 2 décimales.',
            'montant_oeuvre_sociale.numeric' => 'Le montant de l’œuvre sociale doit être un nombre.',
            'montant_oeuvre_sociale.min' => 'Le montant de l’œuvre sociale ne peut pas être négatif.',
            'montant_oeuvre_sociale.decimal' => 'Le montant de l’œuvre sociale ne doit pas avoir plus de 2 décimales.',
            'est_actif.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }
}
