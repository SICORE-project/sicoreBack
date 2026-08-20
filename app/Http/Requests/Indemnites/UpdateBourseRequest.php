<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'montant_mensuel' => ['nullable', 'numeric', 'min:0'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
