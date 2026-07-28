<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 100);
            $table->text('message');
            $table->string('type', 30)->default('info'); // info, warning, error, success
            $table->string('url', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};