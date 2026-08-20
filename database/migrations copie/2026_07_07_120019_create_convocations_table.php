<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocations', function (Blueprint $table) {
            $table->id();
            $table->date('date_emission');
            $table->enum('statut', ['en_attente', 'validee', 'annulee'])->default('en_attente');
            $table->string('lieu_examen');
            $table->boolean('ordre_de_mission')->default(false);
            $table->string('lieu_affectation')->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocations');
    }
};
