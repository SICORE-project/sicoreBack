<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('nom', 50);
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->string('chef_lieu', 50)->nullable();
            $table->integer('population')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('region_id');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departements');
    }
};