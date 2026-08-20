<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class RembourserFraisDeplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant_approuve' => ['nullable', 'numeric', 'min:0'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }
}
