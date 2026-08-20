<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'email'=>[
                'required',
                'email'
            ],

            'reset_token'=>[
                'required',
                'string'
            ],

            'password'=>[
                'required',
                'string',
                'min:8',
                'confirmed'
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est obligatoire pour modifier le mot de passe.',
            'email.email' => 'Le format de l\'email est invalide.',
            'reset_token.required' => 'Le reset token est obligatoire.',
            'reset_token.string' => 'Le reset token doit etre une chaine de caracteres valide.',
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caracteres.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}