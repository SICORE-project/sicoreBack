<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\PolitiqueArchivageAccuse.
 * Table de configuration (généralement une seule ligne active), utilisée
 * par AccuseReceptionController::politiqueArchivage().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('politiques_archivage_accuses')) {
            return;
        }

        Schema::create('politiques_archivage_accuses', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('duree_conservation_annees')->default(5);
            $table->boolean('acces_admin_seul')->default(true);

            $table->foreignId('modifie_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('politiques_archivage_accuses');
    }
};
