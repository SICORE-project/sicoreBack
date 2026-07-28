<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array(mb_strtolower((string) $this->user()->role?->libelle), ['administrateur', 'super administrateur'], true);
    }

    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'login' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/', 'min:3', 'max:50', Rule::unique('users', 'login')],
            'telephone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'structure_id' => ['nullable', 'integer', Rule::exists('lieu_de_services', 'id')],
            'ia_id' => ['nullable', 'integer', Rule::exists('ias', 'id')],
            'ief_id' => ['nullable', 'integer', Rule::exists('iefs', 'id')],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'login.unique' => 'Ce login est déjà utilisé.',
            'login.regex' => 'Le login ne peut contenir que des lettres, chiffres, points, tirets et tirets bas.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
