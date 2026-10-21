<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lieu_de_services', 'est_actif')) {
            Schema::table('lieu_de_services', function (Blueprint $table) {
                $table->boolean('est_actif')->default(true)->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lieu_de_services', 'est_actif')) {
            Schema::table('lieu_de_services', function (Blueprint $table) {
                $table->dropColumn('est_actif');
            });
        }
    }
};
