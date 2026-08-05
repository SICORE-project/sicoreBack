<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.'
            ], 401);
        }


        if (!$user->role || $user->role->slug !== $role) {

            return response()->json([
                'success' => false,
                'message' => 'Accès réservé au rôle : '.$role
            ], 403);
        }


        return $next($request);
    }
}

