<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplines', function (Blueprint $table): void {
            if (! Schema::hasColumn('disciplines', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('disciplines', 'statut')) {
                $table->string('statut', 10)->default('actif')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('disciplines', function (Blueprint $table): void {
            if (Schema::hasColumn('disciplines', 'statut')) {
                $table->dropColumn('statut');
            }
            if (Schema::hasColumn('disciplines', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
