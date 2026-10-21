<?php

namespace App\Services\Administration;

use App\Models\Admin\User;
use App\Models\indemnites;
use App\Services\Administration\Personnel\DrhScope;
use Illuminate\Database\Eloquent\Builder;

class DecpcScope extends DrhScope
{
    public function describe(User $user): array
    {
        if (strtoupper((string) $user->lieuService?->type) !== 'DECPC') {
            return ['type' => 'aucun', 'id' => null];
        }

        return parent::describe($user);
    }

    public function beneficiaries(User $user): Builder
    {
        return $this->apply(User::query(), $user);
    }

    public function indemnites(User $user): Builder
    {
        return indemnites::query()->whereIn('utilisateur_id', $this->beneficiaries($user)->select('users.id'));
    }
}
