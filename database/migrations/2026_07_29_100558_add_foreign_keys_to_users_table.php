<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('enseignant_id')->references('id')->on('enseignants')->onDelete('set null');
            $table->foreign('lieu_service_id')->references('id')->on('lieu_de_services')->onDelete('set null');
            $table->foreign('ief_id')->references('id')->on('iefs')->onDelete('set null');
            $table->foreign('ia_id')->references('id')->on('ias')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['enseignant_id']);
            $table->dropForeign(['lieu_service_id']);
            $table->dropForeign(['ief_id']);
            $table->dropForeign(['ia_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
    }
};