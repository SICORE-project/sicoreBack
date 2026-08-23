<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocations', function (Blueprint $table) {
            if (! Schema::hasColumn('convocations', 'session')) {
                $table->string('session', 150)->nullable()->after('objet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('convocations', function (Blueprint $table) {
            $table->dropColumn('session');
        });
    }
};
