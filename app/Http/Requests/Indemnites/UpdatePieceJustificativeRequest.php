<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePieceJustificativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'max:100'],
            'convocation_id' => ['nullable', 'integer', 'exists:convocations,id'],
            'date_depot' => ['nullable', 'date'],
            'statut' => ['nullable', 'string', 'max:50'],
        ];
    }
}
