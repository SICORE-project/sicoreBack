<?php

namespace App\Http\Middleware;

use App\Models\Parametrage\LieuService;
use App\Models\Personnel\Enseignant;
use App\Services\Administration\Personnel\DrhScope;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrhPersonnelAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->isDrh() && ! $request->isMethodSafe()) {
            abort(403, 'Utilisez le circuit des lots de recrutement.');
        }
        $teacherId = $request->route('id');
        if (! $request->isMethodSafe() && $teacherId && \Illuminate\Support\Facades\Schema::hasTable('recruitment_members')
            && DB::table('recruitment_members')->where('enseignant_id',$teacherId)->exists()) {
            abort_if($request->hasAny(['est_actif','date_prise_service','type_engagement']),403,'Utilisez la prise de service ou la décision de changement de statut.');
        }
        if (! $user?->isDrh() && ! $user?->hasRole('agent_decpc')) {
            return $next($request);
        }

        $scope = $user->hasRole('agent_decpc')
            ? app(\App\Services\Administration\DecpcScope::class) : app(DrhScope::class);
        $id = $request->route('id') ?? $request->route('enseignant');
        $id = $id instanceof Enseignant ? $id->id : $id;
        $teacher = $id ? $scope->apply(Enseignant::query(), $user)->findOrFail($id) : null;
        if (! $request->isMethodSafe() && ! $request->isMethod('DELETE')) {
            if ($request->filled('lieu_service_id')) {
                $destination = LieuService::findOrFail($request->input('lieu_service_id'));
                abort_unless($scope->allows($user, [
                    'lieu_service_id' => $destination->id,
                    'ia_id' => $destination->ia_id,
                    'ief_id' => $destination->ief_id,
                ]), 403);
            }
            abort_unless($scope->allows($user, array_replace(
                $teacher?->getAttributes() ?? [],
                $request->only(['ia_id', 'ief_id', 'lieu_service_id'])
            )), 403);
            if ($request->has('compte_bancaire')) {
                abort_unless($user->hasPermission('enseignants.comptes_bancaires.manage'), 403);
            }
        }

        // Une écriture et sa trace sont validées dans la même transaction.
        try {
            return DB::transaction(function () use ($request, $next, $user, $id) {
                $response = $next($request);
                if ($response->getStatusCode() >= 400) {
                    throw new HttpResponseException($response);
                }
                if ($response->getStatusCode() < 400) {
                    $createdId = $request->isMethod('POST') && method_exists($response, 'getData')
                        ? ($response->getData(true)['data']['id'] ?? null) : null;
                    DB::table('personnel_audit_logs')->insert([
                        'user_id' => $user->id,
                        'enseignant_id' => $id ?? $createdId,
                        'action' => $request->method(),
                        'route' => $request->route()->uri(),
                        'changed_fields' => $request->isMethodSafe() ? null : json_encode(array_keys($request->except(['password', 'token']))),
                        'created_at' => now(),
                    ]);
                }

                return $response;
            });
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        }
    }
}
