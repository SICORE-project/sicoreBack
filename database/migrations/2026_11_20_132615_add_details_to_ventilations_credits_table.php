<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventilations_credits', function (Blueprint $table) {
            $table->foreignId('corps_enseignant_id')->nullable();
            $table->foreignId('ia_id')->nullable();
            $table->foreignId('ief_id')->nullable();

            $table->string('centre_execution')->nullable();
            $table->string('budget')->nullable();
            $table->string('activite')->nullable();
            $table->string('imputation_budgetaire')->nullable();
            $table->string('numero_autorisation')->nullable();
            $table->string('numero_carton')->nullable();
            $table->string('engagement')->nullable();

            $table->enum('type_ventilation', [
                'salaire',
                'prime_scolaire',
            ])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ventilations_credits', function (Blueprint $table) {
            $table->dropColumn([
                'corps_enseignant_id',
                'ia_id',
                'ief_id',
                'centre_execution',
                'budget',
                'activite',
                'imputation_budgetaire',
                'numero_autorisation',
                'numero_carton',
                'engagement',
                'type_ventilation',
            ]);
        });
    }
};