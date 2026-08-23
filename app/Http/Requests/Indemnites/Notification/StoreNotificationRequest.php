<?php

namespace App\Http\Requests\Indemnites\Notification;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // à sécuriser avec une policy/gate admin
    }
    public function rules(): array
    {
        return [
            'titre'   => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
            'type'    => ['nullable', 'in:info,warning,error,success'],
            'url'     => ['nullable', 'string', 'max:255'],

            // Ciblage explicite par ids
            'user_ids'   => ['sometimes', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],

            // Ciblage par critère (adapte les tables/colonnes à ton schéma réel)
            'filters'                  => ['sometimes', 'array'],
            'filters.role_id'   => ['sometimes'], // scalaire ou tableau
            'filters.role_id.*' => ['integer', 'exists:roles,id'],
            'filters.lieu_service_id'  => ['sometimes', 'integer', 'exists:lieu_de_services,id'],
        ];
    }

}
