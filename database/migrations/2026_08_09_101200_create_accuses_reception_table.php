<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\AccuseReception.
 * Statuts observés dans AccuseReceptionController : en_attente, signe, archive.
 * `source_type`/`source_id` : polymorphisme léger pour rattacher l'accusé à
 * différentes origines (convocation, remboursement, bourse...) sans FK stricte,
 * conservé tel quel car aucun contrôleur n'exploite encore de relation Eloquent
 * polymorphique dessus.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accuses_reception')) {
            return;
        }

        Schema::create('accuses_reception', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();
            $table->foreignId('modele_id')->nullable()->constrained('modeles_accuses_reception')->nullOnDelete();
            $table->foreignId('beneficiaire_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('convocation_id')->nullable()->constrained('convocations')->nullOnDelete();

            $table->string('session', 100)->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('objet', 255)->nullable();
            $table->text('contenu')->nullable();
            $table->dateTime('recu_at')->nullable();

            $table->enum('statut', ['en_attente', 'signe', 'archive'])->default('en_attente');
            $table->string('statut_dossier', 100)->nullable();

            $table->string('type_signature', 100)->nullable();
            $table->string('signataire_nom', 150)->nullable();
            $table->string('signature_chemin')->nullable();
            $table->dateTime('signe_at')->nullable();

            $table->foreignId('archive_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archive_at')->nullable();
            $table->date('conserver_jusqu_au')->nullable();

            $table->timestamps();

            $table->index('statut');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accuses_reception');
    }
};
