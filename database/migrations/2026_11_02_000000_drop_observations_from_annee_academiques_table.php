<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('annee_academiques', 'observations')) {
            Schema::table('annee_academiques', function (Blueprint $table): void {
                $table->dropColumn('observations');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('annee_academiques', 'observations')) {
            Schema::table('annee_academiques', function (Blueprint $table): void {
                $table->text('observations')->nullable()->after('date_cloture');
            });
        }
    }
};
