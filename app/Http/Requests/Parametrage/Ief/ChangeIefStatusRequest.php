<?php

namespace App\Http\Requests\Parametrage\Ief;

use Illuminate\Foundation\Http\FormRequest;

class ChangeIefStatusRequest extends FormRequest
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
            'est_actif.required' => 'Le nouveau statut de l’IEF est obligatoire.',
            'est_actif.boolean' => 'Le statut de l’IEF doit être vrai ou faux.',
        ];
    }
}