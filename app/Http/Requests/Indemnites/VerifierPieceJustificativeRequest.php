<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class VerifierPieceJustificativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conforme' => ['required', 'boolean'],
            'commentaire_verification' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
