<?php

namespace App\Services\Administration;

use App\Models\admin\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use App\Models\Parametrage\Ia;
use App\Models\Parametrage\Ief;

class UserService
{
    private function withIaScope(array $data, ?User $user = null): array
    {
        $roleId = $data['role_id'] ?? $user?->role_id;
        if (\App\Models\Admin\Role::whereKey($roleId)->where('slug', 'enseignant')->exists()) {
            $structure = \App\Models\Parametrage\LieuService::find(array_key_exists('lieu_service_id', $data) ? $data['lieu_service_id'] : $user?->lieu_service_id);
            $ief = $structure?->ief_id ? Ief::find($structure->ief_id) : null;
            if (! $structure || ! $structure->est_actif || strtoupper($structure->type) !== 'IEF'
                || ! $ief || ! Ia::whereKey($ief->ia_id)->exists()
                || ($structure->ia_id && (string) $structure->ia_id !== (string) $ief->ia_id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lieu_service_id' => 'L’enseignant doit être rattaché à une IEF de son IA, via une structure IEF active.',
                ]);
            }
            if (array_key_exists('ia_id', $data) && (string) $data['ia_id'] !== (string) $ief->ia_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'ia_id' => 'L’IEF de rattachement doit appartenir à l’IA sélectionnée.',
                ]);
            }

            return array_merge($data, ['ia_id' => $ief->ia_id, 'ief_id' => $ief->id]);
        }
        if (! \App\Models\Admin\Role::whereKey($roleId)->where('slug', 'gestionnaire_ia')->exists()) {
            return $data;
        }

        $structure = \App\Models\Parametrage\LieuService::find(array_key_exists('lieu_service_id', $data) ? $data['lieu_service_id'] : $user?->lieu_service_id);
        if (! $structure || ! $structure->est_actif || strtoupper($structure->type) !== 'IA'
            || ! $structure->ia_id || ! Ia::whereKey($structure->ia_id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lieu_service_id' => 'Le gestionnaire IA doit être rattaché à une structure IA active disposant d’une IA valide.',
            ]);
        }
        if (array_key_exists('ia_id', $data) && (string) $data['ia_id'] !== (string) $structure->ia_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ia_id' => 'L’IA doit correspondre à celle de la structure de rattachement.',
            ]);
        }

        return array_merge($data, ['ia_id' => $structure->ia_id, 'ief_id' => null]);
    }

    /**
     * Création d'un utilisateur
     */
    public function create(array $data): User
    {
        $data = $this->withIaScope($data);

        // Hash du mot de passe
        $data['password'] = Hash::make($data['password']);


        return User::create($data)->load(['role', 'lieuService']);
    }

    /**
     * Liste des utilisateurs
     */
    public function all(?string $structureType = null)
    {
        return User::with(['role', 'lieuService'])
            ->when($structureType, function ($query, string $type) {
                $query->whereHas('lieuService', function ($structureQuery) use ($type) {
                    $structureQuery->whereRaw('UPPER(type) = ?', [mb_strtoupper($type)]);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Liste paginée des utilisateurs.
     */
    public function paginate(
        int $perPage = 10,
        ?string $structureType = null
    ): LengthAwarePaginator
    {
        return User::with(['role', 'lieuService'])
            ->when($structureType, function ($query, string $type) {
                $query->whereHas('lieuService', function ($structureQuery) use ($type) {
                    $structureQuery->whereRaw('UPPER(type) = ?', [mb_strtoupper($type)]);
                });
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->paginate($perPage);
    }



    /**
     * Trouver un utilisateur
     */
    public function find(int $id): User
    {
        return User::with(['role', 'lieuService'])
            ->findOrFail($id);
    }

    /**
     * Mise à jour utilisateur
     */
    public function update(User $user, array $data): User
    {
        $data = $this->withIaScope($data, $user);

        if(isset($data['password'])){

            $data['password'] = Hash::make(
                $data['password']
            );

        }

        $user->update($data);

        $user->load(['role', 'lieuService']);

        return $user;
    }

    /**
     * Suppression utilisateur
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }
    

    /**
 * Rattacher un utilisateur à une IA
 */
public function assignUserToIa(int $userId, int $iaId): User
{
    // 1. Trouver l'utilisateur
    $user = User::findOrFail($userId);
    
    // 2. ✅ VÉRIFIER QUE L'IA EXISTE
    $ia = Ia::findOrFail($iaId);  // ← Cette ligne vérifie l'existence
    
    // 3. Vérifier que l'utilisateur a le bon rôle
    if (!$user->hasRole('gestionnaire_ia')) {
        throw new \Exception("Cet utilisateur n'a pas le rôle Gestionnaire IA.");
    }

    // 4. Vérifier s'il est déjà rattaché
    if ($user->ia_id) {
        throw new \Exception("Cet utilisateur est déjà rattaché à l'IA : {$user->ia->libelle}");
    }

    // 5. Rattacher
    $this->update($user, ['ia_id' => $iaId]);

    return $user->load(['ia', 'role']);
}

public function revokeUserFromIa(int $userId): User
{
    $user = User::findOrFail($userId);
    if ($user->hasRole('gestionnaire_ia')) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'ia_id' => 'Un gestionnaire IA doit rester rattaché à une IA. Changez son rôle avant de retirer son rattachement.',
        ]);
    }
    $user->update(['ia_id' => null]);

    return $user->load(['ia', 'role']);
}

public function assignUserToIef(int $userId, int $iefId): User
{
    $user = User::findOrFail($userId);
    Ief::findOrFail($iefId);

    if (! $user->hasRole('gestionnaire_ief')) {
        throw new \Exception("Cet utilisateur n'a pas le rôle Gestionnaire IEF.");
    }

    if ($user->ief_id) {
        throw new \Exception('Cet utilisateur est déjà rattaché à une IEF.');
    }

    $user->update(['ief_id' => $iefId]);

    return $user->load(['ief', 'role']);
}

public function revokeUserFromIef(int $userId): User
{
    $user = User::findOrFail($userId);
    $user->update(['ief_id' => null]);

    return $user->load(['ief', 'role']);
}

public function getUserIef(int $userId)
{
    return User::with('ief')->findOrFail($userId)->ief;
}

public function getGestionnairesIef()
{
    return User::whereHas('role', fn ($query) => $query->where('slug', 'gestionnaire_ief'))
        ->with(['ief', 'role'])
        ->get();
}

public function getAvailableGestionnairesIef()
{
    return User::whereHas('role', fn ($query) => $query->where('slug', 'gestionnaire_ief'))
        ->whereNull('ief_id')
        ->with('role')
        ->get();
}

public function getGestionnairesByIef(int $iefId)
{
    Ief::findOrFail($iefId);

    return User::whereHas('role', fn ($query) => $query->where('slug', 'gestionnaire_ief'))
        ->where('ief_id', $iefId)
        ->with(['ief', 'role'])
        ->get();
}
}
