<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class DeposerPieceBourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
