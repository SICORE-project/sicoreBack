<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ias', function (Blueprint $table) {
            $table->boolean('est_actif')
                ->default(true)
                ->after('responsable');
        });
    }

    public function down(): void
    {
        Schema::table('ias', function (Blueprint $table) {
            $table->dropColumn('est_actif');
        });
    }
};