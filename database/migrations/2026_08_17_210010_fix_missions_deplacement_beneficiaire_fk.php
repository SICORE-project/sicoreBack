<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('missions_deplacement')) {
            return;
        }

        if (! Schema::hasColumn('missions_deplacement', 'convocation_id')) {
            Schema::table('missions_deplacement', function (Blueprint $table) {
                $table->foreignId('convocation_id')->nullable()->after('id')
                    ->constrained('convocations')->nullOnDelete();
            });
        }

        // Laravel adapte l'inspection au moteur de base de donnees utilise.
        // On conserve le nom reel de la contrainte, qui peut etre personnalise.
        $contrainte = collect(Schema::getForeignKeys('missions_deplacement'))
            ->first(fn (array $foreignKey) => $foreignKey['columns'] === ['beneficiaire_id']
                && $foreignKey['foreign_table'] === 'users');

        if ($contrainte) {
            Schema::table('missions_deplacement', function (Blueprint $table) use ($contrainte) {
                $table->dropForeign($contrainte['name'] ?? ['beneficiaire_id']);
            });

            Schema::table('missions_deplacement', function (Blueprint $table) {
                $table->foreign('beneficiaire_id')->references('id')->on('enseignants')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Correctif défensif : pas de retour arrière (recréer une FK
        // fausse vers `users` n'a pas de sens).
    }
};
