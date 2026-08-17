<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide les identifiants reçus par POST /api/login.
 *
 * Le contrôleur ne reçoit ainsi que des chaînes présentes, de taille maîtrisée
 * et dont l'adresse e-mail possède un format valide.
 */
class LoginRequest extends FormRequest
{
    /** La route de connexion doit rester accessible sans jeton préalable. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /** Messages transmis au frontend en cas de formulaire incomplet. */
    public function messages(): array
    {
        return [
            'email.required' => 'L’e-mail est obligatoire.',
            'email.email' => 'L’e-mail doit être une adresse valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ];
    }
}
