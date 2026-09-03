<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\TypeAide.
 * Doit être migrée avant demandes_aides (FK type_aide_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('types_aides')) {
            return;
        }

        Schema::create('types_aides', function (Blueprint $table) {
            $table->id();

            $table->string('nom', 150);
            $table->decimal('montant_defaut', 12, 2)->nullable();
            $table->string('periodicite', 50)->nullable(); // ex: unique, mensuelle, annuelle
            $table->text('conditions')->nullable();
            $table->boolean('actif')->default(true);

            $table->timestamps();

            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_aides');
    }
};
