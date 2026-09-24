<?php

namespace App\Services\Parametrage;

use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;
use App\Models\Parametrage\LieuService;

class RegionalStructureService
{
    public function sync(Ia|Ief $entity): LieuService
    {
        $isIa = $entity instanceof Ia;
        $type = $isIa ? 'IA' : 'IEF';
        $key = $isIa ? 'ia_id' : 'ief_id';
        $code = 'ORG-'.$type.'-'.$entity->id;
        $query = LieuService::withTrashed()->where('type', $type)->where($key, $entity->id);
        // Une IEF peut aussi contenir des établissements : ne pas les renommer.
        if (! $isIa) {
            $query->whereIn('code', [$entity->code, $entity->getRawOriginal('code'), $code]);
        }
        $structure = $query->orderBy('id')->first() ?? new LieuService(['code' => $code]);
        $structure->fill([
            'libelle' => mb_substr($entity->libelle, 0, 100),
            'type' => $type,
            'perimetre' => 'regional',
            'ia_id' => $isIa ? $entity->id : $entity->ia_id,
            'ief_id' => $isIa ? null : $entity->id,
            'est_actif' => $entity->est_actif ?? true,
        ]);
        $structure->deleted_at = $entity->deleted_at;
        $structure->save();

        return $structure;
    }
}
