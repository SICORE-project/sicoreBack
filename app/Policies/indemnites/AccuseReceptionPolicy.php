<?php

declare(strict_types=1);

namespace App\Policies\indemnites;

use App\Models\Admin\User;
use App\Models\indemnites\Accuse_reception;

class AccuseReceptionPolicy
{
    public function viewAny(User $user): bool
    {
        //return $user->agent !== null;
       //n true;
       return $user->hasPermission('accuses_reception.view');
    }

    public function view(User $user, Accuse_reception $accuse): bool
    {
        return $user->hasPermission('accuses_reception.view');
    }

    public function create(User $user): bool
    {
        //return $user->agent !== null;
        return $user->hasPermission('accuses_reception.create');
    }

    public function update(
        User $user,
        Accuse_reception $accuse
    ): bool {
        return $user->hasPermission('accuses_reception.update');
    }

    public function delete(
        User $user,
        Accuse_reception $accuse
    ): bool {
        //return $user->agent !== null;
        return $user->hasPermission('accuses_reception.delete');
    }

    public function export(User $user): bool
    {
        //turn $user->agent !== null;
        return $user->hasPermission('accuses_reception.export');

    }
}
