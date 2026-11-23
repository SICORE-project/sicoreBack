
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegations_credits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('annee_academique_id')
                ->constrained('annee_academiques')
                ->restrictOnDelete();

            $table->string('reference_lettre', 191);
            $table->text('objet');

            $table->foreignId('periode_de_paie_id')
                ->constrained('periode_de_paies')
                ->restrictOnDelete();

            $table->date('date_delegation');

            $table->decimal('montant_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('reference_lettre');
            $table->index('date_delegation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegations_credits');
    }
};
