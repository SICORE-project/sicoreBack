<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est obligatoire pour envoyer le code de verification.',
            'email.email' => 'Le format de l\'email est invalide.',
        ];
    }
}