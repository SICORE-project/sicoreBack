<?php

use App\Enums\indemnites\AccuseReceptionStatus;
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
        Schema::create('accuse_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->restrictOnDelete();

            $table->foreignId('agent_deposant_id')
                ->constrained('agents')
                ->restrictOnDelete();

            $table->foreignId('agent_receptionnaire_id')
                ->constrained('agents')
                ->restrictOnDelete();

            $table->string('status', 30)
                ->default(AccuseReceptionStatus::EN_ATTENTE->value);

            $table->dateTime('date_depot');

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'agent_deposant_id',
                'agent_receptionnaire_id',
            ], 'idx_accuse_deposant_receptionnaire');

            $table->index('status');
            $table->index('date_depot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accuse_receptions');
    }
};
