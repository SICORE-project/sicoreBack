<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Admin\Personnel\EnseignantController;
use App\Models\Personnel\Enseignant;
use App\Services\Administration\Personnel\EnseignantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherLiveIdentityTest extends TestCase
{
    public function test_duplicates_are_detected_and_current_teacher_is_excluded(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            foreach (['cni', 'matricule', 'indice', 'email'] as $field) $table->string($field)->nullable();
        });
        $values = ['cni' => '2432567865432', 'matricule' => '201765590/G', 'indice' => '1234', 'email' => 'awa@example.com'];
        DB::table('enseignants')->insert(['id' => 1] + $values);
        $service = $this->createMock(EnseignantService::class);
        $teacher = new Enseignant();
        $teacher->forceFill(['id' => 1]);
        $service->method('find')->willReturn($teacher);
        $controller = new EnseignantController($service);
        foreach ($values as $field => $value) {
            foreach ([null => false, 1 => true] as $id => $available) {
                $request = Request::create('/', 'POST', ['field' => $field, 'value' => $value, 'id' => $id ?: null]);
                $request->setUserResolver(fn () => new class {
                    public function hasRole($role) { return true; }
                    public function perimetre() { return []; }
                });
                $result = $controller->checkIdentity($request)->getData(true);
                self::assertSame($available, $result['available']);
                self::assertSame($available, $result['message'] === '');
            }
        }
    }
}
