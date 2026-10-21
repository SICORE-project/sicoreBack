<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class RelancerConvocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enseignant_ids' => ['nullable', 'array'],
            'enseignant_ids.*' => ['integer', 'exists:enseignants,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
