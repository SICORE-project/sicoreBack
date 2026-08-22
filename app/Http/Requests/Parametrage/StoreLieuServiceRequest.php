<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLieuServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }

        if ($this->has('libelle')) {
            $this->merge(['libelle' => trim((string) $this->input('libelle'))]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('lieu_de_services', 'code')],
            'libelle' => ['required', 'string', 'max:100'],
            'ia_id' => [
                'required',
                'integer',
                Rule::exists('ias', 'id')->whereNull('deleted_at'),
            ],
            'ief_id' => [
                'required',
                'integer',
                Rule::exists('iefs', 'id')->where(fn ($query) => $query
                    ->where('ia_id', $this->input('ia_id'))
                    ->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code est déjà utilisé par un autre lieu de service.',
            'ia_id.exists' => 'L’inspection d’académie sélectionnée est introuvable.',
            'ief_id.exists' => 'L’IEF sélectionnée est introuvable ou n’appartient pas à cette inspection d’académie.',
        ];
    }
}
