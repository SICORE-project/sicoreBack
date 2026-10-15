<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table): void {
            // Existing teachers must be classified explicitly, without guessing from their matricule.
            $table->string('statut_administratif', 20)->nullable();
            $table->unsignedInteger('indice')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table): void {
            $table->dropColumn(['statut_administratif', 'indice']);
        });
    }
};
