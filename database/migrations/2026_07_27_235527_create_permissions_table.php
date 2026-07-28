<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 50)->unique();
            $table->string('module', 50)->nullable(); // parametrage, paie, budget, personnel, admin 
            $table->string('structure', 50)->nullable(); // ias, ief, etc.
            $table->string('action', 50); // create, read, update, delete, export, import, validate
            $table->string('description', 255)->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};