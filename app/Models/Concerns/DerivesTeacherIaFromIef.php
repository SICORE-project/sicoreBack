<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'IEF est la référence de l'affectation : l'IA de l'enseignant est toujours
 * celle de son IEF. Les formulaires refusent une IEF d'une autre IA
 * (ValidatesTeacherHierarchy) ; ce hook couvre tous les autres chemins
 * d'écriture (imports, services) pour les trois modèles de la table enseignants.
 */
trait DerivesTeacherIaFromIef
{
    public static function bootDerivesTeacherIaFromIef(): void
    {
        static::saving(function ($teacher): void {
            if (! $teacher->ief_id || ! ($teacher->isDirty('ief_id') || $teacher->isDirty('ia_id') || $teacher->ia_id === null)) {
                return;
            }
            if (! Schema::hasTable('iefs')) {
                return;
            }
            $iaId = DB::table('iefs')->where('id', $teacher->ief_id)->value('ia_id');
            if ($iaId !== null && (int) $teacher->ia_id !== (int) $iaId) {
                $teacher->ia_id = (int) $iaId;
                if (method_exists($teacher, 'unsetRelation')) {
                    $teacher->unsetRelation('ia');
                }
            }
        });
    }
}
