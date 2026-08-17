<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePayrollAccess
{
    public function handle(Request $request, Closure $next, string $level = 'read'): Response
    {
        $user = $request->user()?->loadMissing('role');
        $role = $user?->role?->libelle;
        $roles = (array) config("payroll.{$level}_roles", []);
        $ability = "payroll:{$level}";

        if (! $user || ! in_array($role, $roles, true) || ! $user->tokenCan($ability)) {
            abort(403, 'Vous ne disposez pas de l’autorisation requise pour cette opération de paie.');
        }

        return $next($request);
    }
}
