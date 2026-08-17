<?php
namespace App\Auth;
use Illuminate\Contracts\Auth\Authenticatable; use Illuminate\Contracts\Auth\UserProvider; use SensitiveParameter;
class SessionUserProvider implements UserProvider {
 public function retrieveById($identifier):?Authenticatable{$d=session('sicore.user');return $d && (string)$d['id']===(string)$identifier?new SicoreUser($d):null;}
 public function retrieveByToken($identifier,#[SensitiveParameter]$token):?Authenticatable{return null;}
 public function updateRememberToken(Authenticatable $user,#[SensitiveParameter]$token):void{}
 public function retrieveByCredentials(#[SensitiveParameter]array $credentials):?Authenticatable{return null;}
 public function validateCredentials(Authenticatable $user,#[SensitiveParameter]array $credentials):bool{return false;}
 public function rehashPasswordIfRequired(Authenticatable $user,#[SensitiveParameter]array $credentials,bool $force=false):void{}
}
