<?php

namespace App\Services;

use App\Models\Enseignant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Read Personnel/Paramétrage without saving or reconfiguring the teacher. */
class PayrollTeacherProfile
{
    private array $references = [];

    private function reference(string $table, ?int $id): ?object
    {
        if (! $id) { return null; }
        $key = $table.':'.$id;
        if (! array_key_exists($key, $this->references)) { $this->references[$key] = DB::table($table)->find($id); }
        return $this->references[$key];
    }

    public function describe(Enseignant $teacher): array
    {
        $status = $this->reference('statuts_enseignant', $teacher->statut_enseignant_id);
        $diploma = $this->reference('diplomes', $teacher->diplome_id);
        $categoryId = $teacher->categorie_id ?: $diploma?->categorie_id;
        $category = $this->reference('categories', $categoryId);
        $type = match (Str::upper((string) $status?->code)) {
            'PC' => 'contractuel', 'VAC' => 'vacataire',
            default => match (true) {
                str_contains(Str::lower((string) $status?->libelle), 'contractuel') => 'contractuel',
                str_contains(Str::lower((string) $status?->libelle), 'vacataire') => 'vacataire',
                default => $status ? null : $teacher->type_engagement,
            },
        };
        $level = $diploma ? $this->diplomaLevel($diploma->libelle) : $teacher->payroll_diploma_level;
        return [
            'engagement_type' => $type, 'status_id' => $status?->id,
            'status_label' => $status?->libelle,
            'diploma_id' => $diploma?->id, 'diploma_label' => $diploma?->libelle,
            'diploma_level' => $level, 'category_id' => $category?->id,
            'category_label' => $category?->libelle,
            'category_level' => $teacher->payroll_category_level,
            'base_salary' => $diploma ? $diploma->salaire_brut : null,
        ];
    }

    public function prepare(Enseignant $teacher): Enseignant
    {
        $profile = $this->describe($teacher);
        $error = fn ($message) => ValidationException::withMessages([
            'personnel' => ($teacher->matricule ?: '#'.$teacher->id).' : '.$message,
        ]);
        if (! in_array($profile['engagement_type'], ['contractuel', 'vacataire'], true)) {
            throw $error('renseignez le statut PC ou VAC dans Personnel.');
        }
        // Existing payroll-only profiles remain readable; new administrative dossiers
        // use their diploma's salary, not an inferred category/grid row.
        if (! $profile['diploma_id']) {
            if ($teacher->payroll_profile_configured_at) { return $teacher; }
            throw $error('renseignez le diplôme et son salaire dans Personnel / Paramétrage.');
        }
        if ((float) $profile['base_salary'] <= 0) {
            throw $error('le diplôme sélectionné ne possède pas de salaire positif dans Paramétrage.');
        }
        if ($profile['engagement_type'] === 'contractuel' && ! $profile['diploma_level']) {
            throw $error('le diplôme « '.$profile['diploma_label'].' » ne correspond pas à un barème IRD connu.');
        }
        $prepared = clone $teacher;
        $prepared->setAttribute('type_engagement', $profile['engagement_type']);
        $prepared->setAttribute('salaire_base', $profile['base_salary']);
        $prepared->setAttribute('administrative_payroll', $profile);
        return $prepared;
    }

    private function diplomaLevel(string $label): ?string
    {
        $label = preg_replace('/[^A-Z0-9]+/', '_', Str::upper(Str::ascii(trim($label))));
        return match ($label) {
            'CAP', 'BEP', 'LICENCE' => $label,
            'BAC', 'BT', 'BAC_BT' => 'BAC_BT',
            'BTS', 'DUEL', 'DUES', 'BTS_DUEL_DUES' => 'BTS_DUEL_DUES',
            'MASTER', 'MAITRISE', 'MASTER_MAITRISE' => 'MASTER_MAITRISE',
            default => null,
        };
    }
}
