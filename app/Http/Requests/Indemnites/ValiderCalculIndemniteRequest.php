<?php

namespace App\Http\Requests\Indemnites;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Utilisée pour POST /indemnites/valider-calcul.
 * L'identifiant de l'indemnité est transmis dans le corps de la requête
 * car la route ne comporte pas de {id} (voir routes/modules/indemnites.php).
 */
class ValiderCalculIndemniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:indemnites,id'],
            'commentaire_validation' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
