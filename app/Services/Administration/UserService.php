<?php

namespace App\Services\Administration;

use App\Models\admin\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
    public function all(?string $structureType = null)
    {
        return User::with(['role', 'structureOrganisationnelle'])
            ->when($structureType, function ($query, string $type) {
                $query->whereHas('structureOrganisationnelle', function ($structureQuery) use ($type) {
                    $structureQuery->whereRaw('UPPER(type) = ?', [mb_strtoupper($type)]);
                });
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    /**
     * Liste paginée des utilisateurs.
     */
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return User::with('role')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->paginate($perPage);
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
