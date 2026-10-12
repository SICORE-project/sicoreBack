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
            UserPresence::touch($request->user());
        }
        return $next($request);
    }
}
