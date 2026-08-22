<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts_financieres', function (Blueprint $table) {
            $table->string('sigle', 30)->nullable()->after('libelle');
            $table->string('type_institution', 50)->nullable()->after('sigle');
            $table->boolean('est_actif')->default(true)->after('type_institution');
            $table->index('type_institution');
            $table->index('est_actif');
        });
    }

    public function down(): void
    {
        Schema::table('instituts_financieres', function (Blueprint $table) {
            $table->dropIndex(['type_institution']);
            $table->dropIndex(['est_actif']);
            $table->dropColumn(['sigle', 'type_institution', 'est_actif']);
        });
    }
};
