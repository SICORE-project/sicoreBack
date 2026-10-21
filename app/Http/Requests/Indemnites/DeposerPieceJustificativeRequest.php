<?php

namespace App\Http\Requests\Indemnites;

use App\Models\Indemnite\piece_justificatives;
use Illuminate\Foundation\Http\FormRequest;

class DeposerPieceJustificativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(piece_justificatives::TYPES))],
            'convocation_id' => ['nullable', 'integer', 'exists:convocations,id'],
            'enseignant_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'centre_id' => ['nullable', 'integer', 'exists:convocation_centres,id'],
            'depositaire_id' => ['nullable', 'integer', 'exists:users,id'],
            // 100 Ko max (voir demande utilisateur).
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:100'],
        ];
    }
}
