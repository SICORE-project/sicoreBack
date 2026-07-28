<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lieu_de_services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            $table->foreignId('ia_id')->nullable()->constrained('ias')->onDelete('set null');
            $table->foreignId('ief_id')->nullable()->constrained('iefs')->onDelete('set null');
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lieu_de_services');
    }
};