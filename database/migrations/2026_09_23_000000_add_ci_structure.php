<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL : Laravel ne sait pas modifier un enum (contrainte CHECK) via ->change().
            DB::statement('ALTER TABLE lieu_de_services DROP CONSTRAINT IF EXISTS lieu_de_services_type_check');
            DB::statement('ALTER TABLE lieu_de_services ALTER COLUMN type DROP NOT NULL');
            DB::statement("ALTER TABLE lieu_de_services ADD CONSTRAINT lieu_de_services_type_check CHECK (type IN ('DRH', 'DAGE', 'DECPC', 'CI', 'IA', 'IEF'))");
        } else {
            Schema::table('lieu_de_services', function (Blueprint $table) {
                $table->enum('type', ['DRH', 'DAGE', 'DECPC', 'CI', 'IA', 'IEF'])->nullable()->change();
            });
        }

        if (! DB::table('lieu_de_services')->where('code', 'CI')->exists()) {
            DB::table('lieu_de_services')->insert([
                'code' => 'CI',
                'libelle' => 'Cellule Informatique',
                'type' => 'CI',
                'perimetre' => 'national',
                'est_actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Conserver la structure et son type pour les utilisateurs déjà rattachés.
    }
};
