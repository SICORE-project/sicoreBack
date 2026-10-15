<?php

namespace App\Services\Administration\Personnel;

use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Builder;

class DrhScope
{
    public function describe(User $user): array
    {
        $structure = $user->lieuService;
        if (! $structure || ! $structure->est_actif) {
            return ['type' => 'aucun', 'id' => null];
        }
        // Les restrictions individuelles restent prioritaires sur la structure nationale.
        foreach (['ief_id', 'ia_id'] as $column) {
            if ($user->$column) {
                return ['type' => $column, 'id' => (int) $user->$column];
            }
        }
        if ($structure->perimetre === 'national' && in_array(strtoupper($structure->type), ['DRH', 'DAGE', 'DECPC'], true)) {
            return ['type' => 'national', 'id' => null];
        }
        foreach (['ief_id', 'ia_id'] as $column) {
            if ($structure->$column) {
                return ['type' => $column, 'id' => (int) $structure->$column];
            }
        }

        return in_array(strtoupper($structure->type), ['DRH', 'DAGE', 'DECPC', 'IA', 'IEF'], true)
            ? ['type' => 'aucun', 'id' => null]
            : ['type' => 'lieu_service_id', 'id' => (int) $structure->id];
    }

    public function apply(Builder $query, User $user): Builder
    {
        $scope = $this->describe($user);

        return match ($scope['type']) {
            'national' => $query,
            'aucun' => $query->whereRaw('1 = 0'),
            default => $query->where($scope['type'], $scope['id']),
        };
    }

    public function allows(User $user, array $attributes): bool
    {
        $scope = $this->describe($user);

        return $scope['type'] === 'national'
            || ($scope['type'] !== 'aucun' && (int) ($attributes[$scope['type']] ?? 0) === $scope['id']);
    }
}
