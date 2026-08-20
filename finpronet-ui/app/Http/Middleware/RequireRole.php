<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
class RequireRole { public function handle(Request $request,Closure $next,string ...$roles):Response { $role=(string)data_get($request->user()?->data,'role.libelle'); abort_unless(in_array(mb_strtolower($role),array_map('mb_strtolower',$roles),true),403,'Accès interdit.'); return $next($request); } }
