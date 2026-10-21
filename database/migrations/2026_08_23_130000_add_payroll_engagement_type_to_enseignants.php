<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('enseignants', 'type_engagement')) {
            Schema::table('enseignants', function (Blueprint $table): void {
                $table->string('type_engagement', 30)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('enseignants', 'type_engagement')) {
            Schema::table('enseignants', function (Blueprint $table): void {
                $table->dropColumn('type_engagement');
            });
        }
    }
};
