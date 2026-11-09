<?php
namespace Tests\Unit;

use App\Http\Controllers\Api\Parametrage\LieuServiceController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralDirectionCreationTest extends TestCase
{
    public function test_super_admin_can_create_a_central_direction_without_ia_or_ief(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('lieu_de_services', function (Blueprint $table) {
            $table->id();
            foreach (['code', 'libelle', 'type', 'perimetre'] as $field) $table->string($field);
            $table->unsignedBigInteger('ia_id')->nullable();
            $table->unsignedBigInteger('ief_id')->nullable();
            $table->boolean('est_actif');
            $table->timestamps();
            $table->softDeletes();
        });
        $request = Request::create('/', 'POST', ['code' => 'DAGE-02', 'libelle' => 'Direction centrale', 'type' => 'DAGE']);
        $request->setUserResolver(fn () => new class { public function hasRole($role) { return $role === 'super_admin'; } });
        $response = (new LieuServiceController())->storeDirection($request);
        self::assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('lieu_de_services', ['code' => 'DAGE-02', 'perimetre' => 'national', 'ia_id' => null, 'ief_id' => null, 'est_actif' => true]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new LieuServiceController())->storeDirection($request);
    }

    public function test_other_roles_cannot_create_a_direction(): void
    {
        $request = Request::create('/', 'POST', []);
        $request->setUserResolver(fn () => new class { public function hasRole($role) { return false; } });
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new LieuServiceController())->storeDirection($request);
    }
}
