<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Modification d'UN metier deja rattache a un centre (fiche "Modifier" —
 * section Métiers). Memes regles que StoreConvocationCentreMetierRequest.
 */
class UpdateConvocationCentreMetierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metier' => ['required', 'string', 'max:255'],
        ];
    }
}
