<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * periode_de_paies devient un vrai référentiel des 12 mois :
 *  - numero_mois (1 à 12, unique) remplace la déduction du mois à partir du
 *    code ou du libellé ;
 *  - date_debut, date_fin et payroll_period_id sont supprimées : une ligne du
 *    référentiel (« NOVEMBRE ») sert pour toutes les années et ne doit pointer
 *    vers aucune période datée.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('periode_de_paies', 'numero_mois')) {
            Schema::table('periode_de_paies', function (Blueprint $table): void {
                $table->unsignedTinyInteger('numero_mois')->nullable()->after('libelle');
            });
        }

        // Reprise : dernière utilisation de l'ancienne déduction du mois.
        $taken = DB::table('periode_de_paies')->whereNotNull('numero_mois')->pluck('numero_mois')->map(fn ($m) => (int) $m)->all();
        foreach (DB::table('periode_de_paies')->whereNull('numero_mois')->orderBy('id')->get() as $row) {
            $month = $this->guessMonth($row->code, $row->libelle);
            if ($month !== null && ! in_array($month, $taken, true)) {
                DB::table('periode_de_paies')->where('id', $row->id)->update(['numero_mois' => $month]);
                $taken[] = $month;
            }
        }

        Schema::table('periode_de_paies', function (Blueprint $table): void {
            $table->unique('numero_mois');
        });

        // Obligatoire dès que toutes les lignes ont pu être reprises.
        if (! DB::table('periode_de_paies')->whereNull('numero_mois')->exists()) {
            Schema::table('periode_de_paies', function (Blueprint $table): void {
                $table->unsignedTinyInteger('numero_mois')->nullable(false)->change();
            });
        }

        if (Schema::hasColumn('periode_de_paies', 'payroll_period_id')) {
            Schema::table('periode_de_paies', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('payroll_period_id');
            });
        }
        $dates = array_values(array_filter(['date_debut', 'date_fin'], fn ($c) => Schema::hasColumn('periode_de_paies', $c)));
        if ($dates !== []) {
            Schema::table('periode_de_paies', function (Blueprint $table) use ($dates): void {
                $table->dropColumn($dates);
            });
        }
    }

    public function down(): void
    {
        Schema::table('periode_de_paies', function (Blueprint $table): void {
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('payroll_period_id')->nullable()->constrained('payroll_periods')->restrictOnDelete();
        });
        Schema::table('periode_de_paies', function (Blueprint $table): void {
            $table->dropUnique(['numero_mois']);
            $table->dropColumn('numero_mois');
        });
    }

    private function guessMonth(string $code, string $libelle): ?int
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $code)) {
            return (int) substr($code, -2);
        }
        if (preg_match('/^(0?[1-9]|1[0-2])$/', $code)) {
            return (int) $code;
        }
        $names = ['JANVIER', 'FEVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 'JUILLET', 'AOUT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DECEMBRE'];
        // Le libellé d'abord : « JUI » est ambigu (juin / juillet), « JUILLET » ne l'est pas.
        foreach ([$libelle, $code] as $value) {
            $normalized = Str::upper(Str::ascii(trim($value)));
            foreach ($names as $index => $name) {
                if ($normalized === $name || preg_match('/^'.$name.'\s+\d{4}$/', $normalized)) {
                    return $index + 1;
                }
            }
        }
        foreach ([$libelle, $code] as $value) {
            $normalized = Str::upper(Str::ascii(trim($value)));
            foreach ($names as $index => $name) {
                if ($normalized === substr($name, 0, 3)) {
                    return $index + 1;
                }
            }
        }

        return null;
    }
};
