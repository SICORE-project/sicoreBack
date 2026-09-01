<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('enseignants', 'indice')) {
            return;
        }

        Schema::table('enseignants', function (Blueprint $table) {
            $table->decimal('indice', 10, 2)->nullable()->after('categorie_personnel');
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropColumn('indice');
        });
    }
};
