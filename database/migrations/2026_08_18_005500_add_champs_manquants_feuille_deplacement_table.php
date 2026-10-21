<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->string('ordre_service_emetteur', 255)->nullable()->after('ordre_service_date');
            $table->string('arrete_somme', 255)->nullable()->after('avance_total');
            $table->date('date_fait_avance')->nullable()->after('avance_versee');
        });
    }

    public function down(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->dropColumn(['ordre_service_emetteur', 'arrete_somme', 'date_fait_avance']);
        });
    }
};
