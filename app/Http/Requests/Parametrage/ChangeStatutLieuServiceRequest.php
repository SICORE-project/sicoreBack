<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStatutLieuServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('actif') && ! $this->has('est_actif')) {
            $this->merge(['est_actif' => $this->input('actif')]);
        }
    }

    public function rules(): array
    {
        return [
            'est_actif' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'est_actif.required' => 'Le statut de l’établissement est obligatoire.',
            'est_actif.boolean' => 'Le statut de l’établissement doit être un booléen.',
        ];
    }
}
