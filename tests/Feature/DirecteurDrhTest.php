<?php

namespace Tests\Feature;

use App\Models\Admin\Role;

require_once __DIR__.'/AgentDrhTest.php';

class DirecteurDrhTest extends AgentDrhTest
{
    protected function setUp(): void
    {
        parent::setUp();
        // Exécuter le même contrat d'accès avec le rôle historique du directeur.
        Role::where('slug', 'agent_drh')->update(['slug' => 'drh', 'nom' => 'DRH']);
        auth()->user()->unsetRelation('role');
    }

    public function test_me_returns_navigation_and_seeder_preserves_extra_permissions(): void
    {
        $this->getJson('/api/me')->assertOk()
            ->assertJsonPath('user.role.slug', 'drh')
            ->assertJsonPath('user.drh.dashboard_path', '/drh/dashboard');
    }
}
