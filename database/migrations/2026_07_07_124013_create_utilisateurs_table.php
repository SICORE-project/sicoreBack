<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('genre', ['masculin', 'feminin'])->nullable();
            $table->date('date_naiss')->nullable();
            $table->string('date_lieu')->nullable()->comment('lieu de naissance');
            $table->string('login')->unique();
            $table->string('password');

            $table->foreignId('enseignant_id')
                ->nullable()
                ->constrained('enseignants')
                ->nullOnDelete();

            $table->foreignId('role_id')
                ->nullable()
                ->constrained('roles')
                ->nullOnDelete();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};
