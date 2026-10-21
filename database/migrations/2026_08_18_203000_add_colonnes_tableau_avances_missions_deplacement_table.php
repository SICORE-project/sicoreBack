<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->text('indication_requisitions')->nullable()->after('avance_indemnite_partielle_taux');
            $table->text('poids_bagages_mobilier')->nullable()->after('indication_requisitions');
        });
    }

    public function down(): void
    {
        Schema::table('missions_deplacement', function (Blueprint $table) {
            $table->dropColumn(['indication_requisitions', 'poids_bagages_mobilier']);
        });
    }
};
