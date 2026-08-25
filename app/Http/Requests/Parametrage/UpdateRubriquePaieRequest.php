<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Validation\Rule;

class UpdateRubriquePaieRequest extends StoreRubriquePaieRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = [
            'required',
            'string',
            'max:20',
            'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
            Rule::unique('rubrique_paies', 'code')->ignore($this->route('id')),
        ];

        return $rules;
    }
}
