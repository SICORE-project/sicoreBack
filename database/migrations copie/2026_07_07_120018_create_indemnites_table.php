<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A previous interrupted migration can leave the table created but
        // without this foreign key. Complete it instead of trying to recreate it.
        if (Schema::hasTable('indemnites')) {
            Schema::table('indemnites', function (Blueprint $table) {
                $table->foreign('type_indemnite_id')
                    ->references('id')
                    ->on('type_indemnites')
                    ->cascadeOnDelete();
            });

            return;
        }

        Schema::create('indemnites', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant', 12, 2);
            $table->integer('nombre_copies')->nullable();
            $table->boolean('ordre_de_mission')->default(false);
            $table->string('lieu_affectation')->nullable();
            $table->integer('indice')->nullable();
            $table->integer('nombre_heures')->nullable();
            $table->integer('nombre_kilometrages')->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('type_indemnite_id')
                ->constrained('type_indemnites')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indemnites');
    }
};
