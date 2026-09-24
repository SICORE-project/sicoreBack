<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enseignants', fn (Blueprint $table) => $table->unsignedInteger('indice')->nullable());
    }

    public function down(): void
    {
        Schema::table('enseignants', fn (Blueprint $table) => $table->dropColumn('indice'));
    }
};
