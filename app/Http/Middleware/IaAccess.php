<?php

namespace App\Http\Middleware;

use App\Services\Administration\IaScope;
use App\Services\RecruitmentAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class IaAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user?->hasRole('gestionnaire_ia') || $request->is('api/logout')) {
            return $next($request);
        }
        try {
            $ia = app(IaScope::class)->id($user);
            abort_if($request->has('ia_id') && (! is_scalar($request->input('ia_id')) || (string) $request->input('ia_id') !== (string) $ia), 403);
            // Les opérations nationales utilisent d'autres interfaces et autorisations.
            abort_unless($request->is('api/ia/*', 'api/recruitment/*', 'api/me'), 403);
            foreach (['ief_id' => 'iefs', 'lieu_service_id' => 'lieu_de_services'] as $field => $table) {
                if ($request->filled($field)) {
                    abort_unless(is_scalar($request->input($field)) && DB::table($table)->where('id', $request->input($field))->where('ia_id', $ia)->whereNull('deleted_at')->exists(), 403);
                }
            }
            if ($request->is('api/recruitment/*')) {
                $members = app(RecruitmentAccess::class)->members($user);
                if ($request->route('member')) {
                    abort_unless((clone $members)->where('m.id', $request->route('member'))->exists(), 403);
                }
                if ($request->is('api/recruitment/batches/*')) {
                    abort_unless((clone $members)->where('m.batch_id', $request->route('id'))->exists(), 403);
                }
                if ($request->route('event')) {
                    $event = DB::table('recruitment_events')->find($request->route('event'));
                    abort_unless($event && (clone $members)->where('m.batch_id', $event->batch_id)
                        ->when($event->member_id, fn ($q) => $q->where('m.id', $event->member_id))->exists(), 403);
                    if (! $event->member_id) {
                        // Un document commun peut contenir tous les agents du lot.
                        abort_unless((clone $members)->where('m.batch_id', $event->batch_id)->count()
                            === DB::table('recruitment_members')->where('batch_id', $event->batch_id)->count(), 403);
                    }
                }
            }

            return DB::transaction(function () use ($request, $next, $user) {
                $response = $next($request);
                if ($response->getStatusCode() === 403) {
                    DB::table('personnel_audit_logs')->insert([
                        'user_id' => $user->id, 'action' => 'acces_refuse', 'route' => $request->path(), 'created_at' => now(),
                    ]);
                }
                if ($response->getStatusCode() < 400) {
                    DB::table('personnel_audit_logs')->insert([
                        'user_id' => $user->id, 'action' => $request->method(), 'route' => $request->path(),
                        'changed_fields' => $request->isMethodSafe() ? null : json_encode(array_keys($request->except(['password', 'token', 'document']))),
                        'created_at' => now(),
                    ]);
                }

                return $response;
            });
        } catch (HttpExceptionInterface $exception) {
            if ($exception->getStatusCode() === 403) {
                DB::table('personnel_audit_logs')->insert([
                    'user_id' => $user->id, 'action' => 'acces_refuse', 'route' => $request->path(), 'created_at' => now(),
                ]);
            }
            throw $exception;
        }
    }
}
