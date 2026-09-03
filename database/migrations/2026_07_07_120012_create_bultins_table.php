<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bultins', function (Blueprint $table) {
            $table->id();
            $table->string('matricule');
            $table->date('mois_validite');
            $table->date('date_enregistrement');
            $table->string('numero_ordre');
            $table->decimal('net_a_payer', 12, 2);

            //$table->foreignId('enseignant_id')
              //  ->constrained('enseignants')
                //->cascadeOnDelete();

           // $table->foreignId('ia_id')
              //  ->nullable()
               // ->constrained('ias')
               // ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bultins');
    }
};
