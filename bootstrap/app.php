<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/login');
        $middleware->alias(['permission' => PermissionMiddleware::class,'role' => RoleMiddleware::class,]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) return null;

            $databaseMessage = mb_strtolower($e->getMessage());
            $isForeignKeyDelete = $request->isMethod('DELETE')
                && $e instanceof QueryException
                && (
                    in_array((string) ($e->errorInfo[0] ?? ''), ['23000', '23001', '23503'], true)
                    || str_contains($databaseMessage, 'foreign key')
                    || str_contains($databaseMessage, 'restrict violation')
                    || str_contains($databaseMessage, 'integrity constraint violation')
                );

            $status = match (true) { $isForeignKeyDelete => 409, $e instanceof ValidationException => 422, $e instanceof AuthenticationException => 401,
                $e instanceof ModelNotFoundException => 404, $e instanceof HttpExceptionInterface => $e->getStatusCode(), default => 500 };
            $message = match (true) { $isForeignKeyDelete => 'Suppression impossible : cet élément est associé à d’autres données. Supprimez ou dissociez d’abord les éléments liés.',
                $status === 401 => 'Non authentifié.', $status === 403 => 'Accès interdit.', $status === 404 => 'Ressource introuvable.',
                $status === 429 => 'Trop de tentatives. Réessayez plus tard.', $status === 500 => 'Une erreur interne est survenue.', default => $e->getMessage() ?: 'Requête invalide.' };
            return response()->json(array_filter(['message' => $message,
                'errors' => $e instanceof ValidationException ? $e->errors() : null]), $status);
        });
    })->create();
