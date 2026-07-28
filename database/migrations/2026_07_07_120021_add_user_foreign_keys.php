<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('enseignant_id')->references('id')->on('enseignants')->nullOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            $table->foreign('ief_id')->references('id')->on('iefs')->nullOnDelete();
            $table->foreign('ia_id')->references('id')->on('ias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['enseignant_id']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['ief_id']);
            $table->dropForeign(['ia_id']);
        });
    }
};
