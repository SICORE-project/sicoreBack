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
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            $table->foreignId('ia_id')->constrained('ias')->onDelete('cascade');
            $table->string('adresse', 255)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('responsable', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iefs');
    }
};