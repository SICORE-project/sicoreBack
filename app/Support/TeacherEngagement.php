<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Type d'engagement d'un enseignant (fonctionnaire, contractuel, vacataire) pour les statistiques.
 *
 * Le formulaire et l'import enregistrent le statut de l'enseignant (statut_enseignant_id)
 * sans toujours remplir la colonne type_engagement. Comme la paie, on se base donc d'abord
 * sur le statut ; la colonne type_engagement ne sert que si l'enseignant n'a pas de statut
 * reconnu (anciens enregistrements).
 */
class TeacherEngagement
{
    /**
     * Limite la requête aux enseignants des types donnés.
     *
     * @param  string|array<int, string>  $types  'fonctionnaire', 'contractuel' et/ou 'vacataire'
     * @param  string  $table  nom ou alias de la table des enseignants dans la requête
     */
    public static function filter($query, string|array $types, string $table = 'enseignants')
    {
        $types = (array) $types;
        if (! self::usesStatus()) {
            return $query->whereIn($table.'.type_engagement', $types);
        }
        $statusTypes = self::statusTypes();
        $matching = array_keys(array_filter($statusTypes, fn (?string $type): bool => in_array($type, $types, true)));
        $unknown = array_keys(array_filter($statusTypes, fn (?string $type): bool => $type === null));

        return $query->where(function ($query) use ($table, $types, $matching, $unknown): void {
            $query->whereIn($table.'.statut_enseignant_id', $matching)
                ->orWhere(function ($query) use ($table, $types, $unknown): void {
                    $query->where(fn ($query) => $query->whereNull($table.'.statut_enseignant_id')
                        ->orWhereIn($table.'.statut_enseignant_id', $unknown))
                        ->whereIn($table.'.type_engagement', $types);
                });
        });
    }

    /**
     * Nombre d'enseignants par type, en une seule requête.
     *
     * @param  string  $table  nom ou alias de la table des enseignants dans la requête
     * @return array{fonctionnaire: int, contractuel: int, vacataire: int}
     */
    public static function counts($query, string $table = 'enseignants'): array
    {
        $usesStatus = self::usesStatus();
        $statusTypes = $usesStatus ? self::statusTypes() : [];
        $counts = ['fonctionnaire' => 0, 'contractuel' => 0, 'vacataire' => 0];
        // Requête SQL simple (portées Eloquent comprises, ex. enseignants supprimés exclus).
        $base = method_exists($query, 'toBase') ? $query->toBase() : clone $query;
        $columns = array_filter([
            'status_id' => $usesStatus ? $table.'.statut_enseignant_id' : null,
            'engagement' => Schema::hasColumn('enseignants', 'type_engagement') ? $table.'.type_engagement' : null,
        ]);
        if ($columns === []) {
            return $counts;
        }
        $rows = $base->reorder()
            ->selectRaw(collect($columns)->map(fn (string $column, string $alias): string => $column.' as '.$alias)->implode(', ').', COUNT(*) as total')
            ->groupBy(...array_values($columns))
            ->get();

        foreach ($rows as $row) {
            // Même règle que filter() : statut reconnu d'abord, sinon type_engagement.
            $statusId = $row->status_id ?? null;
            $engagement = $row->engagement ?? null;
            $type = match (true) {
                $statusId === null => $engagement,
                array_key_exists((int) $statusId, $statusTypes) => $statusTypes[(int) $statusId] ?? $engagement,
                default => null,
            };
            if (isset($counts[$type])) {
                $counts[$type] += (int) $row->total;
            }
        }

        return $counts;
    }

    /** Sans table des statuts (base incomplète), on garde l'ancien comptage par type_engagement. */
    private static function usesStatus(): bool
    {
        return Schema::hasTable('statuts_enseignant') && Schema::hasColumn('enseignants', 'statut_enseignant_id');
    }

    /** @return array<int, string|null> statut_enseignant_id => type d'engagement (table de quelques lignes) */
    private static function statusTypes(): array
    {
        return DB::table('statuts_enseignant')->get(['id', 'code', 'libelle'])
            ->mapWithKeys(fn (object $status): array => [(int) $status->id => self::typeOf($status)])
            ->all();
    }

    /** Même lecture du statut que la paie (PayrollTeacherProfile) et que HasTeacherEmploymentStatus. */
    private static function typeOf(object $status): ?string
    {
        $code = Str::upper(trim((string) $status->code));
        $label = Str::lower(trim((string) $status->libelle));

        return match (true) {
            $code === 'PC', str_contains($label, 'contractuel') => 'contractuel',
            $code === 'VAC', str_contains($label, 'vacataire') => 'vacataire',
            in_array($code, ['FONC', 'FONCTIONNAIRE'], true), in_array($label, ['fonctionnaire', 'fonctionnaires'], true) => 'fonctionnaire',
            default => null,
        };
    }
}
