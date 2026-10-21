<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addOrMakeNullable('montant_check_off');
        $this->addOrMakeNullable('montant_oeuvre_sociale');
    }

    public function down(): void
    {
        foreach (['montant_check_off', 'montant_oeuvre_sociale'] as $column) {
            if (! Schema::hasColumn('syndicats', $column)) {
                continue;
            }

            DB::table('syndicats')->whereNull($column)->update([$column => 0]);

            Schema::table('syndicats', function (Blueprint $table) use ($column) {
                $table->decimal($column, 12, 2)
                    ->nullable(false)
                    ->default(0)
                    ->change();
            });
        }
    }

    private function addOrMakeNullable(string $column): void
    {
        Schema::table('syndicats', function (Blueprint $table) use ($column) {
            $definition = $table->decimal($column, 12, 2)
                ->nullable()
                ->default(0);

            if (Schema::hasColumn('syndicats', $column)) {
                $definition->change();
            }
        });
    }
};
