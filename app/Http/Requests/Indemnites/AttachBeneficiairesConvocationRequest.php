<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class AttachBeneficiairesConvocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enseignant_ids' => ['required', 'array', 'min:1'],
            'enseignant_ids.*' => ['integer', 'exists:enseignants,id'],
        ];
    }
}
