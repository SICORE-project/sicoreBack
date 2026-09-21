<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DrhIndemnitesAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isDrh()) {
            $permission = $request->isMethodSafe() ? 'indemnites.read' : 'indemnites.manage';
            if (in_array($request->segment(count($request->segments())), ['valider', 'rejeter'], true)) {
                $permission = 'indemnites.validate';
            }
            abort_unless($request->user()->hasPermission($permission), 403);
        }

        return $next($request);
    }
}
