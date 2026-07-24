<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iefs', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('code');
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->string('email')->nullable();
            $table->string('departement')->nullable();

            $table->foreignId('ia_id')
                ->nullable()
                ->constrained('ias')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iefs');
    }
};
