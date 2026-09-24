<?php

namespace App\Http\Requests\Administration\Personnel\Concerns;

use App\Models\Parametrage\CorpsEnseignant;
use App\Models\Personnel\Enseignant;
use Illuminate\Validation\Rule;

trait ValidatesTeacherIdentity
{
    private bool $teacherIdentityTouched = true;

    protected function prepareTeacherIdentity(bool $updating = false): void
    {
        $fields = ['corps_id', 'matricule', 'indice'];
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
        $corps = CorpsEnseignant::find($this->integer('corps_id'));
        return $corps && (in_array(mb_strtolower(trim($corps->libelle)), ['fonctionnaire', 'fonctionnaires'], true)
            || in_array(mb_strtolower(trim((string) $corps->code)), ['fonctionnaire', 'fonc'], true));
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
            'indice.required' => 'L’indice est obligatoire pour le corps Fonctionnaire.',
            'indice.regex' => 'L’indice doit contenir entre 4 et 6 chiffres, sans lettres ni espaces.',
        ];
    }
}
