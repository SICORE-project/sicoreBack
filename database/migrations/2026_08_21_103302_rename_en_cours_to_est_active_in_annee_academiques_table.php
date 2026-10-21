<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('annee_academiques', 'en cours')
            && ! Schema::hasColumn('annee_academiques', 'est_active')) {
            Schema::table('annee_academiques', function (Blueprint $table) {
                $table->renameColumn('en cours', 'est_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('annee_academiques', 'est_active')
            && ! Schema::hasColumn('annee_academiques', 'en cours')) {
            Schema::table('annee_academiques', function (Blueprint $table) {
                $table->renameColumn('est_active', 'en cours');
            });
        }
    }
};
