<?php

namespace App\Http\Requests\Administration\Personnel\Concerns;

use App\Models\Parametrage\StatutEnseignant;
use App\Models\Personnel\Enseignant;
use Illuminate\Validation\Rule;

trait ValidatesTeacherIdentity
{
    private bool $teacherIdentityTouched = true;

    protected function prepareTeacherIdentity(bool $updating = false): void
    {
        $fields = ['statut_enseignant_id', 'corps_id', 'matricule', 'indice'];
        $this->teacherIdentityTouched = ! $updating || $this->hasAny($fields);
        if ($updating && $this->teacherIdentityTouched && ! $this->has($fields) && $this->route('id')) {
            $teacher = Enseignant::query()->find($this->route('id'), $fields);
            if ($teacher) {
                $this->mergeIfMissing($teacher->getAttributes());
            }
        }
        if ($this->teacherIdentityTouched && ! $this->teacherIsFonctionnaire()) {
            $this->merge(['indice' => null]);
        }
    }

    private function teacherIsFonctionnaire(): bool
    {
        $corps = StatutEnseignant::find($this->integer('statut_enseignant_id'));
        return $corps && (in_array(mb_strtolower(trim($corps->libelle)), ['fonctionnaire', 'fonctionnaires'], true)
            || in_array(mb_strtolower(trim((string) $corps->code)), ['fonctionnaire', 'fonc'], true));
    }

    protected function prepareTeacherSituation(): void
    {
        $this->mergeIfMissing(['statut' => 'en_activite']);
        if ($this->filled('situation_matrimoniale')) {
            $this->merge(['est_en_couple' => $this->input('situation_matrimoniale') === 'marie']);
        }
    }

    protected function clearInapplicableTeacherFields(): void
    {
        if (! $this->teacherIsFonctionnaire()) {
            $this->merge(['corps_id' => null]);
        } else {
            $this->merge(array_fill_keys([ 'salaire_brut', 'date_recrutement',
                'annee_recrutement', 'generation', 'diplome_id', 'categorie_id', 'date_fin_contrat', 'observations'], null));
            $this->merge(['statut' => 'en_activite']);
            $this->merge(['situation_matrimoniale' => null, 'est_en_couple' => false,
                'nombre_enfants' => 0, 'nombre_femmes' => 0, 'conjoint_travaille' => false,
                'nombre_parts_fiscales' => 1]);
        }
    }

    protected function teacherIdentityRules(bool $updating = false): array
    {
        $required = $updating && ! $this->teacherIdentityTouched ? ['sometimes', 'required'] : ['required'];
        $length = $this->teacherIsFonctionnaire() ? 6 : 9;
        $unique = Rule::unique('enseignants', 'matricule');
        $uniqueIndice = Rule::unique('enseignants', 'indice');
        if ($updating) {
            $unique->ignore($this->route('id'));
            $uniqueIndice->ignore($this->route('id'));
        }
        return [
            'matricule' => ['bail', ...$required, 'string', 'regex:~\A[0-9]{'.$length.'}/[A-Z]\z~', $unique],
            'indice' => [Rule::requiredIf(fn () => $this->teacherIdentityTouched && $this->teacherIsFonctionnaire()), 'nullable', 'regex:/\A[0-9]{4,6}\z/', $uniqueIndice],
        ];
    }

    protected function teacherIdentityMessages(): array
    {
        return [
            'matricule.string' => 'Le matricule doit être saisi au format chiffres/lettre majuscule.',
            'matricule.regex' => $this->teacherIsFonctionnaire()
                ? 'Le matricule d’un fonctionnaire doit contenir 6 chiffres suivis de / et d’une lettre majuscule (exemple : 543678/F), sans espaces.'
                : 'Le matricule doit contenir 9 chiffres suivis de / et d’une lettre majuscule (exemple : 202409675/H), sans espaces.',
            'indice.unique' => 'Cet indice est déjà attribué à un autre enseignant.',
            'indice.required' => 'L’indice est obligatoire pour le statut Fonctionnaire.',
            'indice.regex' => 'L’indice doit contenir entre 4 et 6 chiffres, sans lettres ni espaces.',
        ];
    }
}
