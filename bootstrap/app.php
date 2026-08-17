<?php

use App\Http\Middleware\EnsurePayrollAccess;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $middleware->alias([
            'payroll.access' => EnsurePayrollAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $status = match (true) {
                $e instanceof ValidationException => 422, $e instanceof AuthenticationException => 401,
                $e instanceof ModelNotFoundException => 404, $e instanceof HttpExceptionInterface => $e->getStatusCode(), default => 500
            };
            $message = match ($status) {
                401 => 'Non authentifié.', 403 => 'Accès interdit.', 404 => 'Ressource introuvable.',
                429 => 'Trop de tentatives. Réessayez plus tard.', 500 => 'Une erreur interne est survenue.', default => $e->getMessage() ?: 'Requête invalide.'
            };

            return response()->json(array_filter(['message' => $message,
                'errors' => $e instanceof ValidationException ? $e->errors() : null]), $status);
        });
    })->create();
