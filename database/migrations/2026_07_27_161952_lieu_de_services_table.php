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
            $table->enum('perimetre', ['national', 'regional'])->index();
            $table->enum('type', ['DRH', 'DAGE', 'DECPC', 'IA', 'IEF'])->index();
            $table->foreignId('ia_id')->nullable()->constrained('ias')->nullOnDelete();
            $table->foreignId('ief_id')->nullable()->constrained('iefs')->nullOnDelete();
            $table->boolean('est_actif')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lieu_de_services');
    }
};
