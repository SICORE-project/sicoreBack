<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /** Le dépôt est fourni par app/Providers/RepositoryServiceProvider.php. */
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /** Vérifie les identifiants et crée un jeton Bearer Sanctum limité dans le temps. */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->users->findByEmailWithRole((string) $request->string('email'));
        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }
        if ($user->login_enabled === false) {
            return response()->json([
                'message' => 'Ce compte importé n’est pas autorisé à se connecter.',
            ], 403);
        }
        if (! $user->role) {
            return response()->json(['message' => 'Ce compte ne dispose d’aucun rôle. Contactez un administrateur.'], 403);
        }
        // Une nouvelle connexion invalide les anciens jetons du même frontend.
        $this->users->revokeFrontendTokens($user);

        return response()->json([
            'token' => $user->createToken(
                'sicore-front',
                $this->abilities($user),
                now()->addMinutes((int) config('payroll.api_token_expiration'))
            )->plainTextToken,
            'user' => $this->payload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->payload($request->user()->loadMissing('role'))]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnexion effectuée.']);
    }

    /** Construit le profil public attendu par la session du frontend. */
    private function payload(User $u): array
    {
        return ['id' => $u->id, 'email' => $u->email, 'nom' => $u->nom, 'prenom' => $u->prenom,
            'enseignant_id' => $u->enseignant_id, 'role' => ['id' => $u->role->id, 'libelle' => $u->role->libelle]];
    }

    /** Attribue uniquement les permissions compatibles avec le rôle du compte. */
    private function abilities(User $u): array
    {
        $role = $u->role?->libelle;
        $abilities = ['payroll:read'];
        if (in_array($role, (array) config('payroll.write_roles'), true)) {
            $abilities[] = 'payroll:write';
        }
        if (in_array($role, (array) config('payroll.close_roles'), true)) {
            $abilities[] = 'payroll:close';
        }

        return $abilities;
    }
}
