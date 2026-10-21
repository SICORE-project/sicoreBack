<?php
namespace App\Models\Concerns;

use App\Models\Parametrage\StatutEnseignant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

trait HasTeacherEmploymentStatus
{
    public static function bootHasTeacherEmploymentStatus(): void
    {
        static::saving(function ($teacher): void {
            if (!Schema::hasTable('statuts_enseignant')) return;
            if ($teacher->isDirty('type_engagement') && in_array($teacher->type_engagement, ['contractuel', 'vacataire'], true)) {
                $status = StatutEnseignant::query()->whereRaw('LOWER(libelle) LIKE ?', ['%'.$teacher->type_engagement.'%'])->first();
                if (!$status) throw ValidationException::withMessages(['statut_enseignant_id' => 'Créez le statut enseignant correspondant dans le paramétrage.']);
                $teacher->statut_enseignant_id = $status->id;
                $teacher->unsetRelation('statutEnseignant');
            }
            if ($teacher->statut_enseignant_id && !$teacher->isFonctionnaire()) {
                $teacher->corps_id = null;
                if (array_key_exists('corps_enseignant_id', $teacher->getAttributes())) $teacher->corps_enseignant_id = null;
                if ($teacher->isDirty('statut_enseignant_id')) $teacher->unsetRelation('corps');
            }
        });
    }

    public function isFonctionnaire(): bool
    {
        $status = $this->statut_enseignant_id ? StatutEnseignant::find($this->statut_enseignant_id) : null;
        return $status && (in_array(mb_strtolower(trim($status->libelle)), ['fonctionnaire', 'fonctionnaires'], true)
            || in_array(mb_strtolower(trim((string) $status->code)), ['fonc', 'fonctionnaire'], true));
    }
}
