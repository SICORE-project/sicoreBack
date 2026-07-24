<?php
namespace App\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
class SicoreUser implements Authenticatable {
 public function __construct(public array $data) {}
 public function getAuthIdentifierName(){return'id';} public function getAuthIdentifier(){return$this->data['id'];}
 public function getAuthPasswordName(){return'password';} public function getAuthPassword(){return null;}
 public function getRememberToken(){return null;} public function setRememberToken($value):void{} public function getRememberTokenName(){return null;}
 public function __get(string $key):mixed{return data_get($this->data,$key);}
}
