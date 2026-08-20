<?php

namespace App\Http\Requests\Parametrage\Ia;

use Illuminate\Foundation\Http\FormRequest;

class ChangeIaStatusRequest extends FormRequest
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
            'est_actif.required' => 'Le nouveau statut est obligatoire.',
            'est_actif.boolean' => 'Le statut doit être vrai ou faux.',
        ];
    }
}