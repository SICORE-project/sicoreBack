<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_indemnites', function (Blueprint $table) {
            $table->id();
            $table->enum('libelle', ['correction', 'surveillance', 'jury', 'deplacement']);
            $table->decimal('prix_unitaire', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_indemnites');
    }
};
