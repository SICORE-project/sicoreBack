<?php

use App\Providers\AppServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    // Enregistre les dépôts qui isolent les requêtes Eloquent des contrôleurs.
    RepositoryServiceProvider::class,
];
