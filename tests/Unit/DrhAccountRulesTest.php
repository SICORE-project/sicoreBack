<?php
namespace Tests\Unit;

use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Http\Middleware\TrackUserPresence;
use App\Models\Admin\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DrhAccountRulesTest extends TestCase
{
    public function test_drh_cannot_be_assigned_on_creation_or_update(): void
    {
        Schema::create('users', function ($t) { $t->id(); $t->integer('role_id')->nullable(); $t->softDeletes(); });
        Schema::create('roles', function ($t) { $t->id(); $t->string('slug'); });
        DB::table('roles')->insert([['id' => 1, 'slug' => 'drh'], ['id' => 2, 'slug' => 'admin']]);
        foreach ([StoreUserRequest::class, UpdateUserRequest::class] as $class) {
            $request = $class::create('/', 'POST');
            $rule = $request->rules()['role_id'];
            $this->assertTrue(Validator::make(['role_id' => 1], ['role_id' => $rule])->fails());
            $this->assertFalse(Validator::make(['role_id' => 2], ['role_id' => $rule])->fails());
        }
    }

    public function test_drh_with_an_existing_token_cannot_access_authenticated_pages(): void
    {
        $user = new User;
        $user->setRelation('role', new \App\Models\Admin\Role(['slug' => 'drh']));
        $request = Request::create('/api/dashboard');
        $request->setUserResolver(fn () => $user);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new TrackUserPresence)->handle($request, fn () => response('ok'));
    }
}
