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

        $data['lieu_service_id'] = $data['structure_organisationnelle_id'] ?? null;
        unset($data['structure_organisationnelle_id']);

        // Hash du mot de passe
        $data['password'] = Hash::make($data['password']);


        return User::create($data)->load(['role', 'structureOrganisationnelle']);
    }



    /**
     * Liste des utilisateurs
     */
    public function all()
    {
        return User::with(['role', 'structureOrganisationnelle'])->get();
    }



    /**
     * Trouver un utilisateur
     */
    public function find(int $id): User
    {
        return User::with(['role', 'structureOrganisationnelle'])
            ->findOrFail($id);
    }



    /**
     * Mise à jour utilisateur
     */
    public function update(User $user, array $data): User
    {

        if (array_key_exists('structure_organisationnelle_id', $data)) {
            $data['lieu_service_id'] = $data['structure_organisationnelle_id'];
            unset($data['structure_organisationnelle_id']);
        }

        if(isset($data['password'])){

            $data['password'] = Hash::make(
                $data['password']
            );

        }


        $user->update($data);

        $user->load(['role', 'structureOrganisationnelle']);

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
