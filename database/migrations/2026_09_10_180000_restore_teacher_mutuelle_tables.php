<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mutuelles')) {
            Schema::create('mutuelles', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->nullable()->unique();
                $table->string('nom');
                $table->string('sigle', 30)->nullable();
                $table->string('telephone', 20)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('adresse')->nullable();
                $table->text('description')->nullable();
                $table->boolean('est_actif')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('enseignant_mutuelle')) {
            Schema::create('enseignant_mutuelle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
                $table->foreignId('mutuelle_id')->constrained('mutuelles')->restrictOnDelete();
                $table->string('numero_affiliation', 50)->nullable();
                $table->date('date_adhesion')->nullable();
                $table->date('date_resiliation')->nullable();
                $table->boolean('est_actif')->default(true);
                $table->timestamps();
                $table->unique(['enseignant_id', 'mutuelle_id']);
            });
        }
    }

    public function down(): void
    {
        // Correctif conservateur : préserver les tables et les adhésions existantes.
    }
};
