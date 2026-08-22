<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFraisDeplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lieu_depart' => ['sometimes', 'string', 'max:255'],
            'lieu_destination' => ['sometimes', 'string', 'max:255'],
            'motif' => ['nullable', 'string', 'max:255'],
            'date_depart' => ['sometimes', 'date'],
            'date_retour' => ['sometimes', 'date', 'after_or_equal:date_depart'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'moyen_transport' => ['nullable', 'string', 'max:100'],
        ];
    }
}
