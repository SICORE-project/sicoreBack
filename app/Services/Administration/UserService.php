<?php

namespace App\Services\Administration;

use App\Models\admin\User;
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


        return User::create($data);
    }



    /**
     * Liste des utilisateurs
     */
    public function all()
    {
        return User::with('role')->get();
    }



    /**
     * Trouver un utilisateur
     */
    public function find(int $id): User
    {
        return User::with('role')
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
}
