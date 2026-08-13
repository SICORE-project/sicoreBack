<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class StoreConvocationCentresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'centres' => ['required', 'array', 'min:1'],
            'centres.*.centre' => ['required', 'string', 'max:255'],
            'centres.*.jury' => ['nullable', 'string', 'max:100'],
            'centres.*.metier' => ['nullable', 'string', 'max:255'],
            'centres.*.chef_centre_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'centres.*.chef_centre_telephone' => ['nullable', 'string', 'max:30'],
        ];
    }
}