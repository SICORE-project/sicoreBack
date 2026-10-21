<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsurePayrollAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsurePayrollAccessTest extends TestCase
{
    public function test_super_administrateur_peut_acceder_aux_pages_de_paie(): void
    {
        config()->set('payroll.read_roles', ['super_admin', 'admin', 'gestionnaire_paie']);

        $request = Request::create('/api/payroll/pages/paie-avance-tabaski');
        $request->setUserResolver(fn () => new PayrollAccessUser('super_admin', ['payroll:read']));

        $response = (new EnsurePayrollAccess)->handle(
            $request,
            fn (): Response => response('OK'),
            'read'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function test_role_non_autorise_recoit_un_acces_interdit(): void
    {
        config()->set('payroll.read_roles', ['super_admin', 'admin', 'gestionnaire_paie']);

        $request = Request::create('/api/payroll/pages/paie-avance-tabaski');
        $request->setUserResolver(fn () => new PayrollAccessUser('enseignant', ['payroll:read']));

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);

        (new EnsurePayrollAccess)->handle($request, fn (): Response => response('OK'), 'read');
    }
}

class PayrollAccessUser
{
    public object $role;

    /** @param array<int, string> $abilities */
    public function __construct(string $role, private readonly array $abilities)
    {
        $this->role = (object) ['slug' => $role];
    }

    public function loadMissing(string $relation): static
    {
        return $this;
    }

    public function tokenCan(string $ability): bool
    {
        return in_array($ability, $this->abilities, true);
    }
}
