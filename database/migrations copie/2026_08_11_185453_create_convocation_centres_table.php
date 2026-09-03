<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un centre d'examen rattache a une convocation (cf. modele papier
 * "convocation jury BT" : plusieurs centres, chacun avec son jury, son
 * metier et son chef de centre). Auparavant cette information n'etait
 * saisie que cote front (etape 2 du formulaire) et jamais persistee.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('convocation_centres')) {
            return;
        }

        Schema::create('convocation_centres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('convocation_id')->constrained('convocations')->cascadeOnDelete();

            $table->string('centre', 255);
            $table->string('jury', 100)->nullable();
            $table->string('metier', 255)->nullable();

            $table->foreignId('chef_centre_id')->nullable()
                ->constrained('enseignants')->nullOnDelete();
            $table->string('chef_centre_telephone', 30)->nullable();

            $table->timestamps();

            $table->index('convocation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocation_centres');
    }
};