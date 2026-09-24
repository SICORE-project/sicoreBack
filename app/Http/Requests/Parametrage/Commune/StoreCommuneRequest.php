<?php

namespace App\Http\Requests\Parametrage\Commune;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;
class StoreCommuneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code')
                ? strtoupper(trim((string) $this->code))
                : $this->code,

            'libelle' => $this->filled('libelle')
                ? trim((string) $this->libelle)
                : $this->libelle,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('communes', 'code'),
            ],

            'libelle' => [
                'required',
                'string',
                'max:100',
            ],

            'region_id' => [
                'required',
                'integer',
                Rule::exists('regions', 'id')
                    ->where(fn ($query) => $query->where('est_actif', true)),
            ],

            'departement_id' => [
                'required',
                'integer',
                Rule::exists('departements', 'id')
                    ->where(fn ($query) => $query->where('est_actif', true)),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    $validator->errors()->has('region_id') ||
                    $validator->errors()->has('departement_id')
                ) {
                    return;
                }

                $exists = DB::table('departements')
                    ->where('id', $this->integer('departement_id'))
                    ->where('region_id', $this->integer('region_id'))
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add(
                        'departement_id',
                        'Le département sélectionné n’appartient pas à la région sélectionnée.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code de la commune est obligatoire.',
            'code.string' => 'Le code doit être une chaîne de caractères.',
            'code.max' => 'Le code ne doit pas dépasser 20 caractères.',
            'code.unique' => 'Ce code de commune existe déjà.',

            'libelle.required' => 'Le libellé de la commune est obligatoire.',
            'libelle.string' => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 100 caractères.',

            'region_id.required' => 'La région est obligatoire.',
            'region_id.integer' => 'La région sélectionnée est invalide.',
            'region_id.exists' => 'La région sélectionnée est inexistante ou inactive.',

            'departement_id.required' => 'Le département est obligatoire.',
            'departement_id.integer' => 'Le département sélectionné est invalide.',
            'departement_id.exists' => 'Le département sélectionné est inexistant ou inactif.',
        ];
    }
}