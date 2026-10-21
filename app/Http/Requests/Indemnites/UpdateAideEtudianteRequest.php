<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAideEtudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif' => ['sometimes', 'string', 'max:1000'],
            'commentaire_etude' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
