<?php

namespace App\Services\Administration;

use App\Models\admin\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use App\Models\Parametrage\Ia;

class UserService
{

    /**
     * Création d'un utilisateur
     */
    public function create(array $data): User
    {

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
    $user->ia_id = $iaId;
    $user->save();

    return $user->load(['ia', 'role']);
}

public function revokeUserFromIa(int $userId): User
{
    $user = User::findOrFail($userId);
    $user->update(['ia_id' => null]);

    return $user->load(['ia', 'role']);
}
}
