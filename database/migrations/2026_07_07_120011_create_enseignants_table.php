<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->string('indice')->nullable();
            $table->date('date_recrutement')->nullable();
            $table->enum('situation_matrimoniale', ['celibataire_sans_enfant', 'marie'])->nullable();
            $table->string('specialite')->nullable()->comment('libelle libre, cf. table specialites pour la relation');
            $table->string('diplome')->nullable()->comment('libelle libre, cf. table diplomes pour la relation');

            $table->foreignId('corps_enseignant_id')
                ->nullable()
                ->constrained('corps_enseignants')
                ->nullOnDelete();

            $table->foreignId('specialite_id')
                ->nullable()
                ->constrained('specialites')
                ->nullOnDelete();

            $table->foreignId('diplome_id')
                ->nullable()
                ->constrained('diplomes')
                ->nullOnDelete();

            $table->foreignId('mutuelle_id')
                ->nullable()
                ->constrained('mutuelles')
                ->nullOnDelete();

            $table->foreignId('institution_financiere_id')
                ->nullable()
                ->constrained('institution_financieres')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignants');
    }
};
