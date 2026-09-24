<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\Indemnites\IndemnitesController;
use App\Models\Personnel\Enseignant;
use App\Services\Administration\DecpcScope;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DecpcAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user?->hasRole('agent_decpc') || $request->is('api/me', 'api/logout')) {
            return $next($request);
        }

        $scope = app(DecpcScope::class);
        $controller = $request->route()->getControllerClass();
        $personnel = $request->is('api/admin/personnel/enseignants', 'api/admin/personnel/enseignants/*');
        $indemnites = $controller === IndemnitesController::class;
        $dashboard = $request->is('api/decpc/dashboard');
        if ($request->is('api/type-indemnites', 'api/type-indemnites/*') && $request->isMethodSafe()) {
            abort_unless($user->hasPermission('indemnites.read'), 403);

            return $next($request);
        }
        // Les autres circuits ne disposent pas encore d'un rattachement DECPC vérifiable.
        if (! $personnel && ! $indemnites && ! $dashboard) {
            $guards = $request->route()->gatherMiddleware();
            abort_unless(collect($guards)->contains(fn ($guard) => str_starts_with($guard, 'permission:')
                || str_starts_with($guard, 'payroll.access')), 403);

            return $next($request);
        }

        if ($indemnites) {
            $action = $request->route()->getActionMethod();
            $permission = match ($action) {
                'index', 'show', 'listeFrais', 'simuler' => 'read',
                'store', 'calculer' => 'create',
                'destroy' => 'delete',
                'validerCalcul' => 'validate',
                default => 'update',
            };
            abort_unless($user->hasPermission('indemnites.'.$permission), 403);
            $id = $request->route('indemnite') ?? $request->route('id')
                ?? ($action === 'validerCalcul' ? $request->input('id') : null);
            if ($id !== null) {
                abort_unless(is_scalar($id), 422);
                $record = $scope->indemnites($user)->findOrFail($id);
                if (! $request->isMethodSafe() && $record->statut === 'valide') {
                    abort(422, 'Une indemnité validée ne peut plus être modifiée.');
                }
            }
            if (! $request->isMethodSafe()) {
                if (in_array($action, ['calculer', 'simuler'], true)) {
                    abort_unless($request->filled('type_indemnite_id'), 422, 'Un barème explicite est requis.');
                }
                if ($request->has('utilisateur_id')) {
                    abort_unless(is_scalar($request->input('utilisateur_id')) && $scope->beneficiaries($user)
                        ->whereKey($request->input('utilisateur_id'))->exists(), 403);
                }
                if (in_array($request->input('statut'), ['valide', 'rejete'], true)) {
                    abort_unless($user->hasPermission('indemnites.validate'), 403);
                }
                // Le calcul DECPC utilise un barème explicite, sans consulter un dossier de convocation non filtré.
                abort_if($request->filled('convocation_id'), 403);
                if ($request->filled('enseignant_id')) {
                    abort_unless(is_scalar($request->input('enseignant_id')) && $scope->apply(Enseignant::query(), $user)
                        ->whereKey($request->input('enseignant_id'))->exists(), 403);
                }
            }
        }

        try {
            return DB::transaction(function () use ($request, $next, $user) {
                $response = $next($request);
                if ($response->getStatusCode() >= 400) {
                    throw new HttpResponseException($response);
                }
                $ids = $request->route()->parameters();
                if (method_exists($response, 'getData')) {
                    $ids['result_id'] = $response->getData(true)['data']['id'] ?? null;
                }
                if ($request->route()->getActionMethod() === 'validerCalcul') {
                    $ids['id'] = $request->input('id');
                }
                DB::table('decpc_audit_logs')->insert([
                    'user_id' => $user->id, 'action' => $request->method(), 'route' => $request->route()->uri(),
                    'resource_ids' => json_encode($ids),
                    'changed_fields' => $request->isMethodSafe() ? null : json_encode(array_keys($request->except(['password', 'token']))),
                    'created_at' => now(),
                ]);

                return $response;
            });
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        }
    }
}
