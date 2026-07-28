<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required_without:email', 'nullable', 'string', 'max:255'],
            'email' => ['required_without:login', 'nullable', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required_without' => 'Le login ou l’e-mail est obligatoire.',
            'email.required_without' => 'Le login ou l’e-mail est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ];
    }
}
