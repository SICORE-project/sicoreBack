<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * Implémentation Eloquent du dépôt des utilisateurs.
 *
 * Toute évolution de la requête d'authentification doit être faite ici, sans
 * modifier le contrôleur ni le format de réponse consommé par le frontend.
 */
class UserRepository implements UserRepositoryInterface
{
    /** Recherche le compte sans exposer son mot de passe dans la réponse JSON. */
    public function findByEmailWithRole(string $email): ?User
    {
        return User::query()
            ->with('role')
            ->where('email', $email)
            ->first();
    }

    /** Supprime uniquement les jetons portant le nom réservé au frontend. */
    public function revokeFrontendTokens(User $user): void
    {
        $user->tokens()
            ->where('name', 'sicore-front')
            ->delete();
    }
}
