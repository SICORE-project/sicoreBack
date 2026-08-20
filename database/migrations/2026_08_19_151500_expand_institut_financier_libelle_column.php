<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts_financieres', function (Blueprint $table) {
            $table->string('libelle', 150)->change();
        });
    }

    public function down(): void
    {
        Schema::table('instituts_financieres', function (Blueprint $table) {
            $table->string('libelle', 50)->change();
        });
    }
};
