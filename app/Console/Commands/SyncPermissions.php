<?php

namespace App\Console\Commands;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Crée/met à jour les permissions, les attribue aux profils de base (config/role-permissions.php) et signale celles utilisées par les routes mais non déclarées';

    public function handle(): int
    {
        $declared = collect(config('permissions', []));

        $created = $updated = 0;
        foreach ($declared as $permission) {
            $model = Permission::updateOrCreate(['slug' => $permission['slug']], collect($permission)->except('roles')->all());
            $model->wasRecentlyCreated ? $created++ : ($model->wasChanged() ? $updated++ : null);
        }
        $this->info("Permissions synchronisées : {$created} créée(s), {$updated} mise(s) à jour, {$declared->count()} au total.");

        foreach ($this->assignDefaults($declared) as $role => $added) {
            $this->line("  {$role} : {$added} permission(s) ajoutée(s)");
        }

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

    /**
     * Ajoute aux profils de base leurs permissions par défaut, sans rien retirer.
     *
     * @return array<string, int> nombre de permissions ajoutées, par profil
     */
    private function assignDefaults(Collection $declared): array
    {
        $catalogue = Permission::query()->get(['id', 'slug', 'groupe']);

        $byRole = $declared
            ->filter(fn (array $permission) => ! empty($permission['roles']))
            ->flatMap(fn (array $permission) => collect($permission['roles'])->map(fn ($role) => [$role, $permission['slug']]))
            ->groupBy(0)
            ->map(fn (Collection $rows) => $rows->pluck(1)->all());

        $added = [];

        foreach (config('role-permissions', []) as $slug => $definition) {
            $role = Role::whereIn('slug', array_merge([$slug], $definition['alias'] ?? []))->first();

            if (! $role) {
                continue;
            }

            $slugs = ! empty($definition['tout'])
                ? $catalogue->pluck('slug')
                : $catalogue->whereIn('groupe', $definition['groupes'] ?? [])->pluck('slug')
                    ->merge($definition['permissions'] ?? [])
                    ->merge($byRole[$slug] ?? []);

            $ids = $catalogue
                ->whereIn('slug', $slugs->diff($definition['sauf'] ?? [])->unique()->all())
                ->pluck('id')
                ->all();

            $before = $role->permissions()->count();
            $role->permissions()->syncWithoutDetaching($ids);
            $added[$role->slug] = $role->permissions()->count() - $before;
        }

        return $added;
    }
}
