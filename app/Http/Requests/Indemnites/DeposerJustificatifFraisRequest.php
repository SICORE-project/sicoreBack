<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class DeposerJustificatifFraisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ligne_frais_id' => ['nullable', 'integer', 'exists:lignes_frais_deplacement,id'],
            'fichier' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }
}
