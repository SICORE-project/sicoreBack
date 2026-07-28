<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syndicats', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 50);
            $table->decimal('checkoff', 255)->nullable();
            $table->string('oeurvre sociale', 20)->nullable();          
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicats');
    }
};