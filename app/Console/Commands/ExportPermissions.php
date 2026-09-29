<?php

namespace App\Console\Commands;

use App\Models\Admin\Permission;
use Illuminate\Console\Command;

class ExportPermissions extends Command
{
    protected $signature = 'permissions:export {--dry-run : Affiche le résultat sans écrire le fichier}';

    protected $description = 'Recopie les permissions de la base dans config/permissions.php (à commiter pour que l\'équipe les reçoive via permissions:sync)';

    public function handle(): int
    {
        $permissions = Permission::orderBy('groupe')->orderBy('module')->orderBy('action')->orderBy('slug')->get();

        $lines = [];
        $groupe = false;
        foreach ($permissions as $p) {
            if ($p->groupe !== $groupe) {
                $groupe = $p->groupe;
                $lines[] = '';
                $lines[] = '    // === '.strtoupper($groupe ?: 'AUTRES').' ===';
            }
            $lines[] = '    ['
                .implode(', ', [
                    "'nom' => ".var_export($p->nom, true),
                    "'slug' => ".var_export($p->slug, true),
                    "'groupe' => ".var_export($p->groupe, true),
                    "'module' => ".var_export($p->module, true),
                    "'action' => ".var_export($p->action, true),
                ]).'],';
        }

        $header = <<<'PHP'
<?php

/*
|--------------------------------------------------------------------------
| Liste unique des permissions de l'application
|--------------------------------------------------------------------------
| Générée par « php artisan permissions:export » à partir de la base, ou éditée
| à la main. Pour donner ces permissions à un autre environnement :
|   php artisan permissions:sync
| Une permission n'a d'effet que si une route la réclame :
|   ->middleware('permission:mon.slug')
*/
return [
PHP;

        $content = $header.implode("\n", $lines)."\n];\n";

        if ($this->option('dry-run')) {
            $this->line($content);
        } else {
            file_put_contents(config_path('permissions.php'), $content);
            $this->info("config/permissions.php mis à jour : {$permissions->count()} permission(s). Pense à le commiter.");
        }

        return self::SUCCESS;
    }
}
