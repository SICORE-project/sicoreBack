<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rubrique_paies', function (Blueprint $table) {
            $table->foreignId('statut_enseignant_id')
                  ->nullable()
                  ->after('type')
                  ->constrained('statuts_enseignant')
                  ->nullOnDelete();
        });
    }
    
    public function down(): void
    {
        Schema::table('rubrique_paies', function (Blueprint $table) {
            $table->dropForeign(['statut_enseignant_id']);
            $table->dropColumn('statut_enseignant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
   
};
