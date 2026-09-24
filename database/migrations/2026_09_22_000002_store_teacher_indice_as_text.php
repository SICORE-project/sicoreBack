<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table): void {
            // Preserve leading zeroes; retain enough space for any previously stored integer.
            $table->string('indice', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table): void {
            $table->unsignedInteger('indice')->nullable()->change();
        });
    }
};
