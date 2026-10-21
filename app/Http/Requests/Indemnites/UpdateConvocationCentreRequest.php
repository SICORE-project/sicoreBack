<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Modification d'UN centre d'examen deja rattache a une convocation
 * (fiche "Modifier" — section Centres d'examen). Memes regles que
 * StoreConvocationCentresRequest, mais sur un objet unique plutot qu'un
 * tableau de centres.
 */
class UpdateConvocationCentreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'centre' => ['required', 'string', 'max:255'],
            'jury' => ['nullable', 'string', 'max:100'],
            'metier' => ['nullable', 'string', 'max:255'],
            'chef_centre_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'chef_centre_telephone' => ['nullable', 'string', 'max:30'],
            'president_jury_id' => ['nullable', 'integer', 'exists:enseignants,id'],
            'president_jury_telephone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
