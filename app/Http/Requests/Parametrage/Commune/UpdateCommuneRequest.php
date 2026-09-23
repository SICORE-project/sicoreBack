<?php

namespace App\Http\Requests\Parametrage\Commune;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCommuneRequest extends FormRequest
{
    /**
     * Autoriser la requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Nettoyer les données avant validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('code')) {
            $data['code'] = strtoupper(
                trim((string) $this->input('code'))
            );
        }

        if ($this->has('libelle')) {
            $data['libelle'] = trim(
                (string) $this->input('libelle')
            );
        }

        $this->merge($data);
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        $communeId = $this->route('id');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('communes', 'code')
                    ->ignore($communeId),
            ],

            'libelle' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'region_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('regions', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('est_actif', true)
                    ),
            ],

            'departement_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('departements', 'id')
                    ->where(
                        fn ($query) =>
                        $query->where('est_actif', true)
                    ),
            ],
        ];
    }

    /**
     * Vérifications supplémentaires.
     *
     * Vérifie notamment que le département sélectionné
     * appartient bien à la région sélectionnée.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {

                /*
                 * Ne pas continuer si region_id ou departement_id
                 * contient déjà une erreur de validation.
                 */
                if (
                    $validator->errors()->has('region_id') ||
                    $validator->errors()->has('departement_id')
                ) {
                    return;
                }

                /*
                 * Récupérer la commune actuelle.
                 */
                $commune = DB::table('communes')
                    ->where('id', $this->route('id'))
                    ->first();

                /*
                 * Si la commune n'existe pas, le Controller
                 * se chargera du 404.
                 */
                if (! $commune) {
                    return;
                }

                /*
                 * Si region_id n'est pas envoyé pendant la modification,
                 * conserver la région actuelle.
                 */
                $regionId = $this->has('region_id')
                    ? $this->integer('region_id')
                    : (int) $commune->region_id;

                /*
                 * Si departement_id n'est pas envoyé,
                 * conserver le département actuel.
                 */
                $departementId = $this->has('departement_id')
                    ? $this->integer('departement_id')
                    : (int) $commune->departement_id;

                /*
                 * Vérifier que le département appartient
                 * réellement à la région.
                 */
                $departementCorrespond = DB::table('departements')
                    ->where('id', $departementId)
                    ->where('region_id', $regionId)
                    ->exists();

                if (! $departementCorrespond) {
                    $validator->errors()->add(
                        'departement_id',
                        'Le département sélectionné n’appartient pas à la région sélectionnée.'
                    );
                }
            },
        ];
    }

    /**
     * Messages de validation.
     */
    public function messages(): array
    {
        return [
            'code.required' =>
                'Le code de la commune est obligatoire.',

            'code.string' =>
                'Le code doit être une chaîne de caractères.',

            'code.max' =>
                'Le code ne doit pas dépasser 20 caractères.',

            'code.unique' =>
                'Ce code de commune existe déjà.',

            'libelle.required' =>
                'Le libellé de la commune est obligatoire.',

            'libelle.string' =>
                'Le libellé doit être une chaîne de caractères.',

            'libelle.max' =>
                'Le libellé ne doit pas dépasser 100 caractères.',

            'region_id.required' =>
                'La région est obligatoire.',

            'region_id.integer' =>
                'La région sélectionnée est invalide.',

            'region_id.exists' =>
                'La région sélectionnée est inexistante ou inactive.',

            'departement_id.required' =>
                'Le département est obligatoire.',

            'departement_id.integer' =>
                'Le département sélectionné est invalide.',

            'departement_id.exists' =>
                'Le département sélectionné est inexistant ou inactif.',
        ];
    }
}