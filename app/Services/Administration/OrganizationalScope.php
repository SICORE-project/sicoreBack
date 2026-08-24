<?php

namespace App\Services\Administration;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Builder;

class OrganizationalScope
{
    public function apply(Builder $query, User $user, string $iaColumn = 'ia_id', string $iefColumn = 'ief_id'): Builder
    {
        $user->loadMissing(['role.typeRole', 'lieuService']);

        if ($user->role?->typeRole?->code === 'systeme') {
            return $query;
        }

        $structure = $user->lieuService;
        if (! $structure || ! $structure->est_actif) {
            return $query->whereRaw('1 = 0');
        }

        return match (strtoupper((string) $structure->type)) {
            'DAGE', 'DRH', 'DECPC' => $query,
            'IA' => $structure->ia_id
                ? $query->where($iaColumn, $structure->ia_id)
                : $query->whereRaw('1 = 0'),
            'IEF' => $structure->ief_id
                ? $query->where($iefColumn, $structure->ief_id)
                : $query->whereRaw('1 = 0'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function allows(User $user, object $resource): bool
    {
        $user->loadMissing(['role.typeRole', 'lieuService']);

        if ($user->role?->typeRole?->code === 'systeme') {
            return true;
        }

        $structure = $user->lieuService;
        if (! $structure || ! $structure->est_actif) {
            return false;
        }

        return match (strtoupper((string) $structure->type)) {
            'DAGE', 'DRH', 'DECPC' => true,
            'IA' => $structure->ia_id !== null
                && (int) $resource->ia_id === (int) $structure->ia_id,
            'IEF' => $structure->ief_id !== null
                && (int) $resource->ief_id === (int) $structure->ief_id,
            default => false,
        };
    }
}
