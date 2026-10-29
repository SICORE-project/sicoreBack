<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/** Permissions par défaut des profils de base : même résultat que `php artisan permissions:sync`. */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('permissions:sync');
    }
}
