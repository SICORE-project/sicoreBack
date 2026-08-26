<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRubriquePaieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? Str::upper(trim($this->code)) : $this->code,
            'libelle' => is_string($this->libelle) ? trim($this->libelle) : $this->libelle,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('rubrique_paies', 'code')],
            'libelle' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['gain', 'retenue'])],
            'periodicite' => ['required', Rule::in(['mensuelle', 'ponctuelle', 'annuelle'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
