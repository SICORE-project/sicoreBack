<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->string('motif');
            $table->decimal('montant', 15, 2);
            $table->date('date_engagement');
            $table->string('reference_operation')->nullable();

            $table->foreignId('delegation_credit_id')
                ->constrained('delegation_credits')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagements');
    }
};
