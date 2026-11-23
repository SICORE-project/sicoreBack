<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;

/**
 * « php artisan serve » avec OPcache activé.
 *
 * Sans OPcache, PHP relit et recompile tout Laravel à chaque requête
 * (environ 7 fois plus lent). Beaucoup d'installations (XAMPP...) le
 * désactivent par défaut : on l'active ici pour le serveur de développement
 * uniquement, sans modifier le php.ini ni la façon de démarrer l'application.
 */
class ServeCommand extends BaseServeCommand
{
    protected function serverCommand()
    {
        $command = parent::serverCommand();

        return [array_shift($command), ...$this->opcacheOptions(), ...$command];
    }

    /** @return list<string> */
    private function opcacheOptions(): array
    {
        $options = [];

        if (! extension_loaded('Zend OPcache')) {
            $file = ini_get('extension_dir').DIRECTORY_SEPARATOR.(PHP_OS_FAMILY === 'Windows' ? 'php_opcache.dll' : 'opcache.so');

            // Sans le module OPcache, le serveur démarre comme avant.
            if (! is_file($file)) {
                return [];
            }

            $options = ['-d', 'zend_extension=opcache'];
        }

        // Les fichiers modifiés sont relus (au plus 2 s après) : rien ne change pour le développement.
        return [...$options, '-d', 'opcache.enable=1', '-d', 'opcache.enable_cli=1',
            '-d', 'opcache.validate_timestamps=1'];
    }
}
