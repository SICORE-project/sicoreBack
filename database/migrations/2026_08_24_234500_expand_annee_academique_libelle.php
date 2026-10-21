<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annee_academiques', function (Blueprint $table): void {
            $table->string('libelle', 100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('annee_academiques', function (Blueprint $table): void {
            $table->string('libelle', 20)->change();
        });
    }
};
