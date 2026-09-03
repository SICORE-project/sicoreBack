<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syndicats', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20)->unique();
            $table->string('libelle', 100)->unique();

            $table->decimal('montant_check_off', 12, 2)
                ->nullable()
                ->default(0);

            $table->decimal('montant_oeuvre_sociale', 12, 2)
                ->nullable()
                ->default(0);

            $table->boolean('est_actif')
                ->default(true)
                ->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndicats');
    }
};
