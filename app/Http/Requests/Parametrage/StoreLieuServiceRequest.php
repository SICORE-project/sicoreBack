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

    public function rules(): array
    {
        return [
            'telephone' => ['nullable', 'string', 'max:20'],
            'libelle' => ['required', 'string', 'max:100'],
            'ia_id' => [
                'required',
                'integer',
                Rule::exists('ias', 'id')->whereNull('deleted_at'),
            ],
            'ief_id' => [
                'required',
                'integer',
                Rule::exists('iefs', 'id')
                    ->where(fn ($query) => $query
                        ->where('ia_id', $this->input('ia_id'))
                        ->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le nom de l’établissement est obligatoire.',
            'ia_id.required' => 'L’IA est obligatoire.',
            'ief_id.required' => 'L’IEF est obligatoire.',
            'ia_id.exists' => 'L’inspection d’académie sélectionnée est introuvable.',
            'ief_id.exists' => 'L’IEF sélectionnée est introuvable ou n’appartient pas à cette inspection d’académie.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('libelle')) {
            $this->merge(['libelle' => trim((string) $this->input('libelle'))]);
        }
    }
}
