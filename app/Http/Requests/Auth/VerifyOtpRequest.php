<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'otp' => [
                'required',
                'digits:6'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L’adresse e-mail est obligatoire pour vérifier le code.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'otp.required' => 'Veuillez saisir le code de vérification reçu par e-mail.',
            'otp.digits' => 'Le code de vérification doit contenir exactement 6 chiffres.',
        ];
    }
}