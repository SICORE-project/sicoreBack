<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class SignerAccuseReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_signature' => ['required', 'in:electronique,manuscrite'],
            'signataire_nom' => ['required', 'string', 'max:150'],
            'signature' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }
}
