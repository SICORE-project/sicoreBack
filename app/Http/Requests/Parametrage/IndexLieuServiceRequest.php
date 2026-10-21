<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLieuServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('statut') && ! $this->has('est_actif')) {
            $statut = $this->input('statut');
            $this->merge(['est_actif' => match ($statut) {
                'actif' => true,
                'inactif' => false,
                default => $statut,
            }]);
        }

        if ($this->filled('sort_by') && ! $this->has('sort')) {
            $this->merge(['sort' => $this->input('sort_by')]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'ia_id' => ['nullable', 'integer', Rule::exists('ias', 'id')->whereNull('deleted_at')],
            'ief_id' => ['nullable', 'integer', Rule::exists('iefs', 'id')->whereNull('deleted_at')],
            'type' => ['nullable', 'string', 'max:50'],
            'est_actif' => ['nullable', 'boolean'],
            'statut' => ['nullable'],
            'sort' => ['nullable', Rule::in(['code', 'libelle'])],
            'sort_by' => ['nullable'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
