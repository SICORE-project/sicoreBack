<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccuseReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modele_id' => ['nullable', 'integer', 'exists:modeles_accuses_reception,id'],
            'objet' => ['sometimes', 'string', 'max:255'],
            'contenu' => ['nullable', 'string'],
            'statut' => ['nullable', 'string', 'max:50'],
        ];
    }
}
