<?php

namespace App\Http\Middleware;

use App\Services\Auth\UserPresence;
use Closure;
use Illuminate\Http\Request;

class TrackUserPresence
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && !$request->is('api/logout')) {
            $request->user()->loadMissing('role');
            abort_if(mb_strtolower((string) $request->user()->role?->slug) === 'drh', 403, 'Le profil DRH ne dispose pas de compte de connexion.');
            UserPresence::touch($request->user());
        }
        return $next($request);
    }
}
