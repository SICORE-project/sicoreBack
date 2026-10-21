<?php

namespace App\Http\Requests\Parametrage\Ief;

use Illuminate\Foundation\Http\FormRequest;

class RattacherIefIaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ia_id' => [
                'required',
                'integer',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ia_id.required' => 'L’IA de destination est obligatoire.',
            'ia_id.integer' => 'L’IA de destination est invalide.',
        ];
    }
}