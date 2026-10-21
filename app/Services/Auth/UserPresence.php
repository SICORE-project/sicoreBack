<?php

namespace App\Services\Auth;

use App\Models\Admin\User;
use Illuminate\Support\Facades\Cache;

class UserPresence
{
    public static function touch(User $user): void
    {
        if ($user->statut !== 'actif') return;
        Cache::put('presence:user:'.$user->id, true, now()->addMinutes(5));
        if ($user->enseignant_id) Cache::put('presence:teacher:'.$user->enseignant_id, true, now()->addMinutes(5));
    }

    public static function forget(User $user): void
    {
        Cache::forget('presence:user:'.$user->id);
        if ($user->enseignant_id) Cache::forget('presence:teacher:'.$user->enseignant_id);
    }

    public static function online(string $type, int $id): bool
    {
        return (bool) Cache::get('presence:'.$type.':'.$id, false);
    }
}
