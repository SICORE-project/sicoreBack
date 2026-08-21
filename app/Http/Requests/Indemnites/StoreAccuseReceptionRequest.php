<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccuseReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modele_id' => ['nullable', 'integer', 'exists:modeles_accuses_reception,id'],
            'beneficiaire_id' => ['required', 'integer', 'exists:users,id'],
            'convocation_id' => ['nullable', 'integer', 'exists:convocations,id'],
            'session' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer'],
            'objet' => ['required', 'string', 'max:255'],
            'contenu' => ['nullable', 'string'],
        ];
    }
}
