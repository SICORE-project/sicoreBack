<?php

namespace App\Http\Requests\Ia;

use App\Http\Requests\Administration\Personnel\UpdateEnseignantRequest;
use App\Models\Personnel\Enseignant;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Mise à jour d'une fiche enseignant par le gestionnaire de l'IA
 * (Gestion du personnel > Enseignants). Réutilise les règles de la fiche (mêmes contrôles qu'en
 * Personnel), limitées aux informations ouvertes aux IA :
 * situation familiale et parts, banque/RIB, dates de service et contrat,
 * affectation dans l'IA, diplôme/catégorie, syndicat, mutuelle.
 */
class UpdateIaTeacherRequest extends UpdateEnseignantRequest
{
    /** Champs que l'IA peut envoyer. */
    public const EDITABLE = [
        'situation_matrimoniale', 'nombre_enfants', 'nombre_femmes', 'conjoint_travaille',
        'compte_bancaire',
        'date_prise_service', 'date_recrutement', 'date_fin_contrat',
        'ief_id', 'lieu_service_id', 'diplome_id', 'categorie_id', 'syndicat', 'mutuelle',
    ];

    /** Champs recalculés par la fiche à partir des champs envoyés. */
    private const DERIVED = [
        'situation_matrimoniale' => ['est_en_couple', 'nombre_parts_fiscales', 'nombre_femmes', 'conjoint_travaille'],
        'nombre_enfants' => ['nombre_parts_fiscales'],
        'nombre_femmes' => ['nombre_femmes'],
        'conjoint_travaille' => ['conjoint_travaille'],
        'diplome_id' => ['salaire_brut'],
        'categorie_id' => ['salaire_brut'],
        'ief_id' => ['ia_id'],
    ];

    /** @var array<int, string> */
    private array $sent = [];

    protected function prepareForValidation(): void
    {
        $this->sent = array_values(array_intersect(self::EDITABLE, array_keys($this->all())));
        // Seuls les champs ouverts aux IA sont lus ; le reste vient de la fiche.
        $this->replace(Arr::only($this->all(), self::EDITABLE));

        $teacher = Enseignant::query()->find($this->route('id'));
        if ($teacher) {
            $this->mergeIfMissing(array_filter($teacher->only([
                'situation_matrimoniale', 'est_en_couple', 'nombre_enfants', 'nombre_femmes', 'conjoint_travaille',
                'ief_id', 'lieu_service_id', 'diplome_id', 'categorie_id', 'statut_enseignant_id', 'corps_id',
            ]), fn ($v) => $v !== null));
            $this->mergeIfMissing(['ia_id' => $teacher->ia_id]);
            if (! $this->has('compte_bancaire')) {
                $account = $teacher->comptesBancaires()->where('est_principal', true)->first()
                    ?? $teacher->comptesBancaires()->first();
                if ($account) {
                    $this->merge(['compte_bancaire' => $account->only([
                        'institut_financier_id', 'code_banque', 'code_guichet', 'numero_compte', 'cle_rib',
                        'iban', 'bic', 'titulaire_compte', 'type_virement',
                    ])]);
                }
            }
            if ($this->filled('ief_id')) {
                // L'IA suit l'IEF (DerivesTeacherIaFromIef) ; le contrôle de périmètre reste fait par ValidatesTeacherHierarchy.
                $this->merge(['ia_id' => \Illuminate\Support\Facades\DB::table('iefs')->where('id', $this->input('ief_id'))->value('ia_id') ?? $teacher->ia_id]);
            }
        }

        parent::prepareForValidation();
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $keep = [
            ...self::EDITABLE, 'est_en_couple', 'nombre_parts_fiscales', 'salaire_brut',
            'ia_id', 'statut_enseignant_id', 'corps_id',
        ];
        $rules = array_filter($rules, function (string $key) use ($keep): bool {
            return in_array(explode('.', $key)[0], $keep, true);
        }, ARRAY_FILTER_USE_KEY);
        if (! $this->has('compte_bancaire')) {
            // Enseignant sans compte et RIB non saisi : rien à contrôler.
            $rules = array_filter($rules, fn ($key) => ! str_starts_with($key, 'compte_bancaire'), ARRAY_FILTER_USE_KEY);
        }

        return [
            ...$rules,
            'mutuelle' => ['sometimes', 'nullable', 'array'],
            'mutuelle.mutuelle_id' => ['nullable', 'integer', Rule::exists('mutuelles', 'id')],
        ];
    }

    /** Données à enregistrer : uniquement ce que l'IA a envoyé, et ce qui en découle. */
    public function editionData(): array
    {
        $keys = $this->sent;
        foreach ($this->sent as $field) {
            array_push($keys, ...(self::DERIVED[$field] ?? []));
        }

        return Arr::only($this->validated(), array_unique($keys));
    }
}
