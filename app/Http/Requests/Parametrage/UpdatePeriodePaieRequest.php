<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Validation\Rule;

class UpdatePeriodePaieRequest extends StorePeriodePaieRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = [
            'required',
            'string',
            'max:20',
            'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
            Rule::unique('periode_de_paies', 'code')->ignore($this->route('id')),
        ];

        return $rules;
    }
}
