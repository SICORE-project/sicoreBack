<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use App\Services\Auth\UserPresence;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserPresenceTest extends TestCase
{
    public function test_presence_expires_and_logout_clears_both_indicators(): void
    {
        Cache::flush();
        $user = new User(['statut' => 'actif', 'enseignant_id' => 19]);
        $user->id = 91;
        $this->assertFalse(UserPresence::online('user', 91));
        UserPresence::touch($user);
        $this->assertTrue(UserPresence::online('user', 91));
        $this->assertTrue(UserPresence::online('teacher', 19));
        $this->travel(6)->minutes();
        $this->assertFalse(UserPresence::online('user', 91));
        $this->assertFalse(UserPresence::online('teacher', 19));
        UserPresence::touch($user);
        UserPresence::forget($user);
        $this->assertFalse(UserPresence::online('user', 91));
        $this->assertFalse(UserPresence::online('teacher', 19));
        $user->statut = 'inactif';
        UserPresence::touch($user);
        $this->assertFalse(UserPresence::online('user', 91));
        $this->travelBack();
    }
}
