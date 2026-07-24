<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\Http; use Tests\TestCase;
class AuthenticationTest extends TestCase {
 public function test_page_metier_redirige_vers_login_sans_session():void{$this->get('/dashboard')->assertRedirect('/');}
 public function test_login_bff_stocke_token_uniquement_en_session():void{Http::fake(['*/api/login'=>Http::response(['token'=>'secret-token','user'=>['id'=>1,'login'=>'bdiaw','nom'=>'Diaw','prenom'=>'Baye','role'=>['id'=>1,'libelle'=>'Administrateur']]])]);$this->post('/login',['login'=>'bdiaw','password'=>'secret123'])->assertRedirect('/dashboard')->assertSessionHas('sicore.token','secret-token');Http::assertSent(fn($r)=>$r->url()===config('services.sicore.url').'/api/login');}
 public function test_logout_revoque_token_et_invalide_session():void{Http::fake(['*/api/logout'=>Http::response(['message'=>'ok'])]);$user=['id'=>1,'login'=>'bdiaw','role'=>['libelle'=>'Administrateur']];$this->actingAs(new \App\Auth\SicoreUser($user))->withSession(['sicore.user'=>$user,'sicore.token'=>'secret-token'])->post('/logout')->assertRedirect('/')->assertSessionMissing('sicore.token');}
}
