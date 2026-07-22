<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ias', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('code');
            $table->string('centre_geo')->nullable();
            $table->string('gouvernance')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ias');
    }
};
