<?php

namespace App\Http\Requests\Parametrage\Specialite;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecialiteStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'est_actif' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'est_actif.required' => 'Le statut de la spécialité est obligatoire.',
            'est_actif.boolean' => 'Le statut de la spécialité doit être vrai ou faux.',
        ];
    }
}