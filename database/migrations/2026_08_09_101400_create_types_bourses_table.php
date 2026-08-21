<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\TypeBourse.
 * Doit être migrée avant attributions_bourses (FK type_bourse_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('types_bourses')) {
            return;
        }

        Schema::create('types_bourses', function (Blueprint $table) {
            $table->id();

            $table->string('nom', 150);
            $table->decimal('montant_mensuel', 12, 2)->nullable();
            $table->unsignedInteger('duree_mois')->nullable();
            $table->text('conditions')->nullable();
            $table->boolean('actif')->default(true);

            $table->timestamps();

            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_bourses');
    }
};
