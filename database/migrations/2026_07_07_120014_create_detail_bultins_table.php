<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_bultins', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant_gains', 12, 2)->default(0);
            $table->decimal('montant_retenus', 12, 2)->default(0);

            $table->foreignId('bultin_id')
                ->constrained('bultins')
                ->cascadeOnDelete();

            $table->foreignId('rubrique_bultin_id')
                ->constrained('rubrique_bultins')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_bultins');
    }
};
