<?php

namespace App\Services\Administration;

use App\Models\admin\User;
use Illuminate\Support\Facades\Hash;

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

}
