<?php
namespace App\Http\Controllers;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller {
 public function login(LoginRequest $request): JsonResponse {
  $identifier=(string)($request->input('login') ?? $request->input('email'));
  $user=User::with('role')->where('email',$identifier)->orWhere('login',$identifier)->first();
  if(!$user || !Hash::check($request->string('password'),$user->password)) return response()->json(['message'=>'Identifiants invalides'],401);
  if(!$user->role) return response()->json(['message'=>'Ce compte ne dispose d’aucun rôle. Contactez un administrateur.'],403);
  $user->tokens()->where('name','finpronet-ui')->delete();
  return response()->json(['token'=>$user->createToken('finpronet-ui')->plainTextToken,'user'=>$this->payload($user)]);
 }
 public function me(Request $request): JsonResponse { return response()->json(['user'=>$this->payload($request->user()->loadMissing('role'))]); }
 public function logout(Request $request): JsonResponse { $request->user()->currentAccessToken()?->delete(); return response()->json(['message'=>'Déconnexion effectuée.']); }
 private function payload(User $u): array { return ['id'=>$u->id,'email'=>$u->email,'nom'=>$u->nom,'prenom'=>$u->prenom,
  'enseignant_id'=>$u->enseignant_id,'role'=>['id'=>$u->role->id,'libelle'=>$u->role->libelle]]; }
}
