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

}
