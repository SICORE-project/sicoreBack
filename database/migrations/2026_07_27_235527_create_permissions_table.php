<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('nom', 50)->unique();
                $table->string('slug', 50)->unique();
                $table->string('groupe', 50)->nullable();
                $table->string('module', 50)->nullable();
                $table->string('action', 50)->nullable();
                $table->string('description', 255)->nullable();
                $table->boolean('est_actif')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};