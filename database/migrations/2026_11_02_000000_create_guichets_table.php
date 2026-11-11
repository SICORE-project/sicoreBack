<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guichets', function (Blueprint $table) {
            $table->string('code', 5);
            $table->foreignId('institut_financier_id')
                ->constrained('instituts_financieres')
                ->cascadeOnDelete();
            $table->string('libelle');
            $table->timestamps();

            $table->unique(['institut_financier_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guichets');
    }
};
