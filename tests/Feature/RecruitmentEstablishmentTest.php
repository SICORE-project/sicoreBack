<?php

namespace Tests\Feature;

use App\Models\Admin\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class RecruitmentEstablishmentTest extends RecruitmentWorkflowTest
{
    public function test_ia_can_select_only_its_active_establishments_without_parametrage_permission(): void
    {
        $ia = User::whereHas('role', fn ($q) => $q->where('slug', 'gestionnaire_ia'))->firstOrFail();
        $this->assertFalse($ia->hasPermission('parametrage.lieux_service.read'));
        DB::table('lieu_de_services')->insert([
            ['id' => 50, 'libelle' => 'Autre IA', 'ia_id' => 99, 'ief_id' => 1, 'est_actif' => true],
            ['id' => 51, 'libelle' => 'Fermé', 'ia_id' => 1, 'ief_id' => 1, 'est_actif' => false],
        ]);
        Sanctum::actingAs($ia, ['*']);
        $this->getJson('/api/recruitment/establishments?ia_id=99')->assertForbidden();
        $response = $this->getJson('/api/recruitment/establishments')->assertOk();
        $this->assertNotEmpty($response->json('data'));
        foreach ($response->json('data') as $lieu) {
            $this->assertSame(1, $lieu['ia_id']);
            $this->assertNotContains($lieu['id'], [50, 51]);
        }
        $drh = User::whereHas('role', fn ($q) => $q->where('slug', 'agent_drh'))->first();
        if ($drh) {
            Sanctum::actingAs($drh, ['*']);
            $this->getJson('/api/recruitment/establishments')->assertForbidden();
        }
    }
}
