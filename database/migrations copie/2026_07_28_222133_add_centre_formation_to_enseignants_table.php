<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->foreignId('centre_formation_id')->nullable()->constrained('centres_formation')->onDelete('set null');
            $table->index('centre_formation_id');
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropForeign(['centre_formation_id']);
            $table->dropColumn('centre_formation_id');
        });
    }
};