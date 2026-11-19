<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Jeton Sanctum dont la date de dernière utilisation (last_used_at) est
 * enregistrée au plus une fois par minute au lieu d'une écriture en base
 * à chaque appel API (environ 12 ms par appel sous SQLite).
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function save(array $options = [])
    {
        $lastUsed = $this->getOriginal('last_used_at');

        if ($this->exists && array_keys($this->getDirty()) === ['last_used_at']
            && $lastUsed !== null && $this->asDateTime($lastUsed)->gt(now()->subMinute())) {
            return true;
        }

        return parent::save($options);
    }
}
