<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Rattachements
{
    public static function compter(string $table, string $colonne, int|string $id, bool $avecSupprimes = false): int
    {
        return DB::table($table)
            ->where($colonne, $id)
            ->when(! $avecSupprimes && Schema::hasColumn($table, 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->count();
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: string}>  $comptes  [nombre, singulier, pluriel]
     */
    public static function message(string $objet, array $comptes): ?string
    {
        $liens = [];

        foreach ($comptes as [$nombre, $singulier, $pluriel]) {
            if ($nombre > 0) {
                $liens[] = $nombre.' '.($nombre > 1 ? $pluriel : $singulier);
            }
        }

        return $liens === [] ? null : "Impossible de supprimer {$objet}. Éléments liés : ".implode(' et ', $liens).'.';
    }
}
