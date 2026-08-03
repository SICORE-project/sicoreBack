<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiement_salaires', function (Blueprint $table) {

            $table->id();

            $table->foreignId('delegation_credit_id')
                  ->constrained('delegation_credits')
                  ->cascadeOnDelete();

            $table->string('nom_agent');

            $table->string('mois');

            $table->decimal('montant',15,2);

            $table->date('date_paiement');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiement_salaires');
    }
};