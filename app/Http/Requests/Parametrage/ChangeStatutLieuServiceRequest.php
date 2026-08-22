<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStatutLieuServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'est_actif.required' => 'Le statut du lieu de service est obligatoire.',
            'est_actif.boolean' => 'Le statut du lieu de service doit être un booléen.',
        ];
    }
}
