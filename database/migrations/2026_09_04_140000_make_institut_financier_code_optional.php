<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts_financieres', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('instituts_financieres', function (Blueprint $table): void {
            $table->string('code', 20)->nullable(false)->change();
        });
    }
};
