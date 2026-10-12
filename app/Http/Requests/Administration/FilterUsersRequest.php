<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterUsersRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom' => ['nullable', 'string', 'max:100'],
            'matricule' => ['nullable', 'string', 'max:30'],
            'ia_id' => ['nullable', 'required_with:ief_id,etablissement_id', 'integer', Rule::exists('ias', 'id')->whereNull('deleted_at')],
            'ief_id' => ['nullable', 'required_with:etablissement_id', 'integer', Rule::exists('iefs', 'id')->where('ia_id', $this->input('ia_id'))->whereNull('deleted_at')],
            'etablissement_id' => ['nullable', 'integer', Rule::exists('lieu_de_services', 'id')->where('ief_id', $this->input('ief_id'))->whereNull('deleted_at')],
            'type_structure' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
