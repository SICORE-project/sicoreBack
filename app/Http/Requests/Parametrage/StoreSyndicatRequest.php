<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreSyndicatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? Str::upper(trim($this->code)) : $this->code,
            'libelle' => is_string($this->libelle) ? trim($this->libelle) : $this->libelle,
            'montant_check_off' => $this->normalizeAmount($this->montant_check_off),
            'montant_oeuvre_sociale' => $this->normalizeAmount($this->montant_oeuvre_sociale),
        ]);
    }

    private function normalizeAmount(mixed $amount): mixed
    {
        return is_string($amount) ? str_replace(',', '.', trim($amount)) : $amount;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:syndicats,code'],
            'libelle' => ['required', 'string', 'max:100', 'unique:syndicats,libelle'],
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
