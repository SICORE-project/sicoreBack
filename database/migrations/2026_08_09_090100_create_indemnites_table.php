<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des indemnités calculées/validées pour un utilisateur.
 *
 * Colonnes déduites de App\Models\indemnites ($fillable, $casts) et des
 * règles de StoreIndemniteRequest / UpdateIndemniteRequest / CalculerIndemniteRequest.
 *
 * NOTE: `bareme_id` est présent dans $fillable du modèle et dans les
 * FormRequests ('bareme_id' => ['nullable','integer']) mais n'est référencé
 * par aucun contrôleur ni aucune table "baremes" existante. Il est donc créé
 * ici en simple colonne (sans contrainte FK) pour ne pas bloquer les writes
 * du modèle tel qu'il existe aujourd'hui — à clarifier avec l'équipe
 * (doublon possible avec type_indemnite_id ?).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('indemnites')) {
            return;
        }

        Schema::create('indemnites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('utilisateur_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('type_indemnite_id')->constrained('type_indemnites')->restrictOnDelete();

            // Voir NOTE ci-dessus : pas de FK tant que la table cible n'existe pas.
            $table->unsignedBigInteger('bareme_id')->nullable();

            $table->decimal('montant', 12, 2)->nullable();
            $table->decimal('montant_base', 12, 2)->nullable();
            $table->decimal('frais_deplacement', 12, 2)->nullable()->default(0);
            $table->decimal('montant_total', 12, 2)->nullable();

            $table->enum('statut', ['brouillon', 'calcule', 'valide', 'rejete'])->default('brouillon');

            // Champs spécifiques selon le mode de calcul du type d'indemnité
            $table->unsignedInteger('nombre_copies')->nullable();
            $table->boolean('ordre_de_mission')->nullable()->default(false);
            $table->string('lieu_affectation', 255)->nullable();
            $table->decimal('indice', 10, 2)->nullable();
            $table->decimal('nombre_heures', 8, 2)->nullable();
            $table->decimal('nombre_kilometrages', 8, 2)->nullable();

            // Validation du calcul
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('commentaire_validation')->nullable();

            $table->timestamps();

            $table->index('statut');
            $table->index('utilisateur_id');
            $table->index('type_indemnite_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indemnites');
    }
};
