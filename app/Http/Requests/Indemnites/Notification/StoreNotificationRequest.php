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
        ];
    }
}
