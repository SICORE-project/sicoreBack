<?php

namespace App\Console\Commands;

use App\Models\Admin\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Crée/met à jour les permissions de config/permissions.php et signale celles utilisées par les routes mais non déclarées';

    public function handle(): int
    {
        $declared = collect(config('permissions', []));

        $created = $updated = 0;
        foreach ($declared as $permission) {
            $model = Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
            $model->wasRecentlyCreated ? $created++ : ($model->wasChanged() ? $updated++ : null);
        }
        $this->info("Permissions synchronisées : {$created} créée(s), {$updated} mise(s) à jour, {$declared->count()} au total.");

        // Permissions exigées par les routes (permission:a,b) mais absentes de la config
        $used = collect(Route::getRoutes())
            ->flatMap(fn ($route) => $route->gatherMiddleware())
            ->filter(fn ($m) => is_string($m) && str_starts_with($m, 'permission:'))
            ->flatMap(fn ($m) => explode(',', substr($m, 11)))
            ->unique();

        $missing = $used->diff($declared->pluck('slug'));
        foreach ($missing as $slug) {
            $this->warn("Utilisée par une route mais non déclarée dans config/permissions.php : {$slug}");
        }

        return $missing->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}
