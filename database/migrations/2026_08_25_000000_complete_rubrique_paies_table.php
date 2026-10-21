<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubrique_paies', function (Blueprint $table): void {
            if (! Schema::hasColumn('rubrique_paies', 'periodicite')) {
                $table->string('periodicite', 20)->default('mensuelle')->index();
            }
            if (! Schema::hasColumn('rubrique_paies', 'est_cotisable')) {
                $table->boolean('est_cotisable')->default(false);
            }
            if (! Schema::hasColumn('rubrique_paies', 'est_imposable')) {
                $table->boolean('est_imposable')->default(false);
            }
            if (! Schema::hasColumn('rubrique_paies', 'est_afficher_bulletin')) {
                $table->boolean('est_afficher_bulletin')->default(true);
            }
            if (! Schema::hasColumn('rubrique_paies', 'taux_defaut')) {
                $table->decimal('taux_defaut', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('rubrique_paies', 'montant_defaut')) {
                $table->decimal('montant_defaut', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('rubrique_paies', 'formule_calcul')) {
                $table->string('formule_calcul', 255)->nullable();
            }
            if (! Schema::hasColumn('rubrique_paies', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('rubrique_paies', 'est_actif')) {
                $table->boolean('est_actif')->default(true)->index();
            }
            if (! Schema::hasColumn('rubrique_paies', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rubrique_paies', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                'periodicite', 'est_cotisable', 'est_imposable', 'est_afficher_bulletin',
                'taux_defaut', 'montant_defaut', 'formule_calcul', 'description', 'est_actif',
                'created_at', 'updated_at',
            ], fn (string $column): bool => Schema::hasColumn('rubrique_paies', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
