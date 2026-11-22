<?php

namespace App\Services\Auth;

use App\Models\Admin\User;
use Illuminate\Support\Facades\Cache;

class UserPresence
{
    public static function touch(User $user): void
    {
        if ($user->statut !== 'actif') return;
        self::refresh('presence:user:'.$user->id);
        if ($user->enseignant_id) self::refresh('presence:teacher:'.$user->enseignant_id);
    }

    /** Présence valable 5 min, renouvelée au plus une fois par minute (évite une écriture par appel API). */
    private static function refresh(string $key): void
    {
        $since = Cache::get($key);
        if (is_int($since) && $since > now()->subMinute()->timestamp) return;
        Cache::put($key, now()->timestamp, now()->addMinutes(5));
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
