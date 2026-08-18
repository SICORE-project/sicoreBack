<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class ImportConvocationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
           
            'fichier' => ['required', 'file', 'mimes:docx', 'max:5120'],

            'type_convocation_id' => ['required', 'integer', 'exists:types_convocation,id'],

            'utilisateur_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
