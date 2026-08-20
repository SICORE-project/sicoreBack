<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiplomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $diplomeId = $this->route('diplome')?->id;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('diplomes', 'code')->ignore($diplomeId),
            ],
            'libelle' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'type' => [
                'nullable',
                'string',
                'in:academique,professionnel',
            ],
            'date_obteention' => [
                'required',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code de diplôme existe déjà.',
            'libelle.unique' => 'Ce libellé de diplôme existe déjà.',
            'date_obteention.required' => 'La date d\'obtention est obligatoire.',
            'date_obteention.date' => 'La date d\'obtention doit être une date valide.',
            'type.in' => 'Le type de diplôme doit être soit "academique" soit "professionnel".',
        ];
    }
}
