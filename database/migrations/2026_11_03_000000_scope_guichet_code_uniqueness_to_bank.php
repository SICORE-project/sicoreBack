<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('guichets', 'guichets_code_unique')) {
            Schema::table('guichets', function (Blueprint $table) {
                $table->dropUnique('guichets_code_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('guichets', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
