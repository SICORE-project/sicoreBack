<?php

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * Contrat d'accès aux utilisateurs nécessaires à l'authentification API.
 *
 * Le contrôleur ne connaît plus la requête Eloquent utilisée pour retrouver
 * un compte. Cette séparation suit le Repository Pattern présenté dans le TP.
 */
interface UserRepositoryInterface
{
    /** Recherche un utilisateur par e-mail avec son rôle déjà chargé. */
    public function findByEmailWithRole(string $email): ?User;

    /** Révoque les anciens jetons créés pour le frontend SICORE. */
    public function revokeFrontendTokens(User $user): void;
}
