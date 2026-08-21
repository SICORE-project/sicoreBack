<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ecran FINPRONET frmDetailDelegation.aspx ("Ventilations") :
     * une delegation porte N lignes ventilees, chacune rattachee a un couple
     * corps d'enseignant / IA / IEF et a une imputation budgetaire complete.
     */
    public function up(): void
    {
        Schema::create('ventilations_delegation', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delegation_credit_id')
                  ->constrained('delegation_credits')
                  ->cascadeOnDelete();

            // Axe d'affectation FINPRONET
            $table->foreignId('corps_enseignant_id')->nullable()
                  ->constrained('corps_enseignants')->nullOnDelete();
            $table->foreignId('ia_id')->nullable()
                  ->constrained('ias')->nullOnDelete();
            $table->foreignId('ief_id')->nullable()
                  ->constrained('iefs')->nullOnDelete();

            // Nomenclature budgetaire
            $table->foreignId('centre_execution_id')->nullable()
                  ->constrained('centres_execution')->nullOnDelete();
            $table->foreignId('budget_id')->nullable()
                  ->constrained('budgets')->nullOnDelete();
            $table->foreignId('activite_id')->nullable()
                  ->constrained('activites')->nullOnDelete();
            $table->string('imputation_budgetaire')->nullable();

            // Autorisation et carton
            $table->string('numero_autorisation')->nullable();
            $table->string('numero_carton')->nullable();

            // Montants
            $table->decimal('montant', 15, 2)->default(0);
            $table->decimal('montant_engagement', 15, 2)->default(0);

            // Etat sur salaire / Etat sur prime scolaire
            $table->enum('type', ['salaire', 'prime_scolaire'])->default('salaire');

            $table->timestamps();

            $table->index(['delegation_credit_id', 'type']);
            $table->index('numero_carton');
            $table->index('numero_autorisation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventilations_delegation');
    }
};
