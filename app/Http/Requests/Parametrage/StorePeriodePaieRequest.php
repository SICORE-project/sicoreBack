<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;   // <-- ajouter

class StorePeriodePaieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code'    => is_string($this->code) ? Str::upper(trim($this->code)) : $this->code,
            'libelle' => is_string($this->libelle) ? trim($this->libelle) : $this->libelle,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                Rule::unique('periode_de_paies', 'code'),
            ],
            'libelle' => ['required', 'string', 'max:100'],
            'numero_mois' => ['required', 'integer', 'between:1,12', Rule::unique('periode_de_paies', 'numero_mois')],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_mois.required' => 'Choisissez le mois (janvier à décembre).',
            'numero_mois.between' => 'Le mois doit être compris entre 1 et 12.',
            'numero_mois.unique' => 'Ce mois est déjà enregistré dans le référentiel.',
        ];
    }

    // ⬇️ Ajouté ici
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('libelle')) {
                    return; // déjà invalide, on n'ajoute pas d'erreur en double
                }

                $exists = \DB::table('periode_de_paies')
                    ->whereRaw('LOWER(libelle) = ?', [mb_strtolower($this->libelle)])
                    ->when($this->route('periode_paie'), fn ($q, $id) => $q->where('id', '!=', $id))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('libelle', 'Ce libellé existe déjà.');
                }
            },
        ];
    }
}