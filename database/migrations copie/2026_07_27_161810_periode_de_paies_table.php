<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_de_paies', function (Blueprint $table) {

            $table->string('code')->unique();
            $table->string('libelle', 50);
            $table->integer('mois');
            $table->integer('annee');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->date('date_paiement')->nullable();
            $table->date('date_limite_saisie')->nullable();
            $table->date('date_limite_validation')->nullable();
            $table->boolean('est_fermee')->default(false);// pour dire si la periode est fermee ou pas
            $table->boolean('est_verrouillee')->default(false);
            $table->foreignId('annee_academique_id')->constrained('annee_academiques')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_de_paies');
    }
};
