<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Parametrage\Diplome;

class StoreDiplomeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('libelle')) {
            $this->merge(['libelle' => mb_strtoupper(trim((string) $this->input('libelle')), 'UTF-8')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => [
                'required',
                'string',
                'max:100',
            ],
            'categorie_id' => ['required', 'integer', 'exists:categories,id', Rule::unique('diplomes', 'categorie_id')->where(fn ($query) => $query->whereRaw('UPPER(TRIM(libelle)) = ?', [$this->input('libelle')]))],
            'salaire_brut' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_id.unique' => 'Cette catégorie est déjà utilisée pour ce diplôme.',
            'categorie_id.required' => 'La catégorie est obligatoire.',
            'categorie_id.exists' => 'La catégorie sélectionnée n’existe pas.',
            'salaire_brut.required' => 'Le salaire brut est obligatoire.',
        ];
    }
}
