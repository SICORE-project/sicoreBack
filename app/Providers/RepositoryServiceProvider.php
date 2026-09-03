<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Relie les contrats de dépôts à leurs implémentations Eloquent.
 *
 * Grâce à ce provider, Laravel injecte UserRepository lorsqu'un contrôleur
 * demande UserRepositoryInterface. Ce fichier est chargé dans bootstrap/providers.php.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /** Enregistre les dépôts applicatifs dans le conteneur de dépendances. */
    public function register(): void
    {
        $this->app->singleton(
            UserRepositoryInterface::class,
            UserRepository::class
        );
    }
}
