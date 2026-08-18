<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lieu_de_services')) {
            return;
        }

        Schema::create('lieu_de_services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            // These reference tables are managed outside this migration set.
            $table->foreignId('ia_id')->nullable();
            $table->foreignId('ief_id')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lieu_de_services');
    }
};
