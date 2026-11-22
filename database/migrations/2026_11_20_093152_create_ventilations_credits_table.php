
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventilations_credits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delegation_credit_id')
                ->constrained('delegations_credits')
                ->cascadeOnDelete();

            // Bénéficiaire : IA, IEF ou lieu de service.
            $table->enum('type_beneficiaire', [
                'IA',
                'IEF',
                'LIEU_SERVICE',
            ]);

            $table->unsignedBigInteger('beneficiaire_id');

            $table->decimal('montant', 15, 2);

            $table->timestamps();

            $table->index([
                'type_beneficiaire',
                'beneficiaire_id',
            ], 'ventilations_beneficiaire_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventilations_credits');
    }
};
