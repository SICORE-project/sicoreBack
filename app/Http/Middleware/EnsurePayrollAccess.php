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
        $permission = match ($level) {
            'close' => 'paie.bulletins.validate',
            'write' => 'paie.bulletins.generate',
            default => 'paie.bulletins.read',
        };
        $allowed = $user && ($user->hasRole('super_admin') || $user->hasPermission($permission));

        if (! $allowed) {
            abort(403, 'Vous ne disposez pas de l’autorisation requise pour cette opération de paie.');
        }

        return $next($request);
    }
}
