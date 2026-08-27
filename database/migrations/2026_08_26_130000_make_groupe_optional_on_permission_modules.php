<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permission_modules')) {
            return;
        }

        Schema::table('permission_modules', function (Blueprint $table) {
            $table->dropForeign(['groupe_id']);
        });

        Schema::table('permission_modules', function (Blueprint $table) {
            $table->unsignedBigInteger('groupe_id')->nullable()->change();
            $table->foreign('groupe_id')->references('id')->on('permission_groupes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('permission_modules')) {
            return;
        }

        Schema::table('permission_modules', function (Blueprint $table) {
            $table->dropForeign(['groupe_id']);
        });

        Schema::table('permission_modules', function (Blueprint $table) {
            $table->foreign('groupe_id')->references('id')->on('permission_groupes')->restrictOnDelete();
        });
    }
};
