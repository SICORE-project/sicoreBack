<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_COLUMNS = [
        'sigle',
        'adresse',
        'telephone',
        'email',
        'site_web',
        'responsable',
        'responsable_titre',
        'taux_cotisation',
    ];

    public function up(): void
    {
        $columns = array_values(array_filter(
            self::LEGACY_COLUMNS,
            fn (string $column): bool => Schema::hasColumn('syndicats', $column),
        ));

        if ($columns !== []) {
            Schema::table('syndicats', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }

        // L'ancien schéma ajoutait un index simple en plus de l'index unique.
        if (Schema::hasIndex('syndicats', 'syndicats_code_index')) {
            Schema::table('syndicats', function (Blueprint $table) {
                $table->dropIndex('syndicats_code_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('syndicats', function (Blueprint $table) {
            $table->string('sigle', 20)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('site_web', 100)->nullable();
            $table->string('responsable', 100)->nullable();
            $table->string('responsable_titre', 50)->nullable();
            $table->decimal('taux_cotisation', 8, 2)->default(0);
        });

        if (! Schema::hasIndex('syndicats', 'syndicats_code_index')) {
            Schema::table('syndicats', function (Blueprint $table) {
                $table->index('code');
            });
        }
    }
};
