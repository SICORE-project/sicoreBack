<?php
namespace App\Http\Controllers;
use App\Auth\SicoreUser; use App\Services\SicoreApi; use Illuminate\Http\Client\ConnectionException; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Illuminate\View\View;
class AuthController extends Controller {
 public function showLogin():View{return view('pages.auth.login');}
 public function login(Request $request,SicoreApi $api):RedirectResponse{
  $data=$request->validate(['login'=>['required','string','max:255'],'password'=>['required','string','max:255']],['login.required'=>'Le login est obligatoire.','password.required'=>'Le mot de passe est obligatoire.']);
  try{$response=$api->login($data);}catch(ConnectionException){return back()->withInput($request->only('login'))->withErrors(['login'=>'Le serveur SICORE est indisponible. Réessayez plus tard.']);}
  if(!$response->successful())return back()->withInput($request->only('login'))->withErrors(['login'=>SicoreApi::message($response)]);
  $user=$response->json('user'); $token=$response->json('token'); if(!is_array($user)||!is_string($token))return back()->withErrors(['login'=>'Réponse invalide du serveur SICORE.']);
  $request->session()->regenerate(); $request->session()->put(['sicore.user'=>$user,'sicore.token'=>$token]); Auth::login(new SicoreUser($user));
  $intended=$request->session()->pull('url.intended'); return redirect()->to($this->internal($intended)?$intended:route('dashboard'));
 }
 public function logout(Request $request,SicoreApi $api):RedirectResponse{
  $token=$request->session()->get('sicore.token'); if(is_string($token)){try{$api->logout($token);}catch(ConnectionException){}}
  Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('login')->with('status','Vous êtes déconnecté.');
 }
 private function internal(mixed $url):bool{if(!is_string($url)||$url==='')return false;$p=parse_url($url);return !isset($p['host'])&&str_starts_with($p['path']??'','/');}
}
