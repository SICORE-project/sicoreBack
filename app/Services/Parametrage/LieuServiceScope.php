<?php

namespace App\Services\Parametrage;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Builder;

class LieuServiceScope
{
    public function apply(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query;
        }

        $user->loadMissing(['role', 'lieuService']);

        if (in_array($user->role?->niveau, ['systeme', 'admin'], true)) {
            return $query;
        }

        if ($user->ief_id) {
            return $query->where('ief_id', $user->ief_id);
        }

        if ($user->ia_id) {
            return $query->where('ia_id', $user->ia_id);
        }

        $structure = $user->lieuService;
        if (! $structure) {
            return $query->whereRaw('1 = 0');
        }

        return match (strtoupper((string) $structure->type)) {
            'DAGE', 'DRH', 'DECPC' => $query,
            'IA' => $structure->ia_id
                ? $query->where('ia_id', $structure->ia_id)
                : $query->whereRaw('1 = 0'),
            'IEF' => $structure->ief_id
                ? $query->where('ief_id', $structure->ief_id)
                : $query->whereRaw('1 = 0'),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
