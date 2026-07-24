<?php
namespace App\Services;
use Illuminate\Http\Client\ConnectionException; use Illuminate\Http\Client\PendingRequest; use Illuminate\Http\Client\Response; use Illuminate\Support\Facades\Http;
class SicoreApi {
 private function request(?string $token=null):PendingRequest { $r=Http::acceptJson()->asJson()->timeout((int)config('services.sicore.timeout',10)); return $token?$r->withToken($token):$r; }
 public function login(array $credentials):Response{return $this->request()->post($this->url('/api/login'),$credentials);}
 public function logout(string $token):Response{return $this->request($token)->post($this->url('/api/logout'));}
 public function get(string $path,string $token):Response{return $this->request($token)->get($this->url($path));}
 private function url(string $path):string{return rtrim((string)config('services.sicore.url'),'/').'/'.ltrim($path,'/');}
 public static function message(Response $r):string{return match($r->status()){401=>'Identifiants invalides.',403=>$r->json('message','Accès interdit.'),422=>collect($r->json('errors',[]))->flatten()->first()??'Données invalides.',429=>'Trop de tentatives. Réessayez dans une minute.',default=>$r->serverError()?'Le service SICORE est temporairement indisponible.':$r->json('message','La requête a échoué.')};}
}
