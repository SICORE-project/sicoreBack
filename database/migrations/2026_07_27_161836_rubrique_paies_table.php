<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubrique_paies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            $table->enum('type', [
                'gain',
                'retenue',
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubrique_paies');
    }
};