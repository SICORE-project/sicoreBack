<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TeacherPersonalAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->hasRole('enseignant')) {
            abort_unless($user->statut === 'actif', 403);
            abort_unless($request->is('api/enseignant/*')
                || $request->is('api/me') || $request->is('api/logout'), 403);
        }

        return $next($request);
    }
}
