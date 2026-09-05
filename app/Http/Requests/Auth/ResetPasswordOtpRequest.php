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
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).+$/',
                'confirmed'
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'reset_token.required' => 'Le jeton de réinitialisation est obligatoire.',
            'reset_token.string' => 'Le jeton de réinitialisation est invalide.',
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.string' => 'Le nouveau mot de passe doit être un texte valide.',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.regex' => 'Le nouveau mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}