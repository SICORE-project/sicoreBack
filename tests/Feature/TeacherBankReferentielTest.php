<?php

namespace Tests\Feature;

use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherBankReferentielTest extends TestCase
{
    public function test_bank_creation_persists_contact_and_banking_fields(): void
    {
        $this->withoutMiddleware([Authenticate::class, PermissionMiddleware::class]);
        Schema::create('instituts_financieres', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            foreach (['sigle', 'type_institution', 'adresse', 'telephone', 'email', 'code_banque', 'code_guichet', 'iban_exemple'] as $field) {
                $table->string($field)->nullable();
            }
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });

        $fields = [
            'libelle' => 'Banque test', 'sigle' => null, 'type_institution' => 'Banque',
            'adresse' => 'Dakar', 'telephone' => '771234567', 'email' => 'banque@example.com',
            'code_banque' => '00123', 'code_guichet' => '00045', 'iban_exemple' => 'SN012345',
            'est_actif' => false,
        ];
        $this->postJson('/api/parametrage/institutions-financieres', $fields)
            ->assertCreated()->assertJsonPath('data.code_banque', '00123')
            ->assertJsonPath('data.code_guichet', '00045')
            ->assertJsonPath('data.iban_exemple', 'SN012345');
        $this->assertDatabaseHas('instituts_financieres', $fields);
        unset($fields['est_actif']);
        $this->putJson('/api/parametrage/institutions-financieres/1', $fields)->assertOk();
        $this->assertDatabaseHas('instituts_financieres', ['id' => 1, 'est_actif' => false]);
        $this->postJson('/api/parametrage/institutions-financieres', $fields)->assertCreated();
        $this->assertDatabaseHas('instituts_financieres', ['id' => 2, 'est_actif' => true]);
    }
}
