<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Validation\Rule;

class UpdatePeriodePaieRequest extends StorePeriodePaieRequest
{
    public function rules(): array
    {
        // On repart des règles du Store (code + libelle)
        $rules = parent::rules();

        // On remplace uniquement la règle du code pour ignorer l'enregistrement courant
        $rules['code'] = [
            'required',
            'string',
            'max:20',
            'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
            Rule::unique('periode_de_paies', 'code')->ignore($this->route('id')),
        ];

        return $rules;
    }

    /**
     * On surcharge after() pour exclure l'enregistrement courant
     * dans la vérification d'unicité du libellé (insensible à la casse).
     */
    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator) {
                if ($validator->errors()->has('libelle')) {
                    return;
                }

                $exists = \DB::table('periode_de_paies')
                    ->whereRaw('LOWER(libelle) = ?', [mb_strtolower($this->libelle)])
                    ->where('id', '!=', $this->route('id'))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('libelle', 'Ce libellé existe déjà.');
                }
            },
        ];
    }
}