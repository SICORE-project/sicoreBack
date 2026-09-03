<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes déduites de App\Models\MissionDeplacement et
 * StoreFraisDeplacementRequest / RembourserFraisDeplacementRequest.
 * Statuts observés dans FraisDeplacementController :
 * brouillon -> calcule -> valide -> rembourse -> cloture (ou rejete).
 * Doit être migrée avant lignes_frais_deplacement et justificatifs_frais_deplacement.
 *
 * CORRIGÉ (2026-08-17) : beneficiaire_id pointait vers `users`, mais le
 * bénéficiaire d'une fiche de déplacement est un membre convoqué
 * (App\Models\Indemnite\Convocations::enseignants(), table `enseignants`),
 * pas forcément un compte utilisateur SICORE (users.enseignant_id est
 * nullable — la plupart des membres de jury n'ont pas de compte). Repointé
 * vers `enseignants`. `declare_par` reste sur `users` (l'agent DAGE qui
 * remplit la fiche, lui a bien un compte). `convocation_id` ajouté pour
 * relier la fiche à son dossier de convocation d'origine (nécessaire pour
 * filtrer les bénéficiaires "dossier complet" — cf. FraisDeplacementController).
 * Si cette migration a déjà été exécutée avant ce correctif, voir
 * 2026_08_17_210010_fix_missions_deplacement_beneficiaire_fk.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('missions_deplacement')) {
            return;
        }

        Schema::create('missions_deplacement', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();

            $table->foreignId('convocation_id')->nullable()->constrained('convocations')->nullOnDelete();
            $table->foreignId('beneficiaire_id')->constrained('enseignants')->restrictOnDelete();
            $table->foreignId('declare_par')->nullable()->constrained('users')->nullOnDelete();

            $table->string('lieu_depart', 255);
            $table->string('lieu_destination', 255);
            $table->string('motif', 255)->nullable();
            $table->date('date_depart');
            $table->date('date_retour');
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->string('moyen_transport', 100)->nullable();

            // Contexte agent utilisé pour le calcul des frais
            $table->string('statut_agent', 100)->nullable();
            $table->decimal('indice_agent', 10, 2)->nullable();
            $table->decimal('salaire_global_annuel', 14, 2)->nullable();
            $table->string('lieu_service', 255)->nullable();

            $table->enum('statut', ['brouillon', 'calcule', 'valide', 'rejete', 'rembourse', 'cloture'])->default('brouillon');
            $table->decimal('montant_calcule', 12, 2)->nullable();
            $table->decimal('montant_approuve', 12, 2)->nullable();

            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('motif_rejet')->nullable();

            $table->date('echeance_paiement')->nullable();
            $table->dateTime('rembourse_le')->nullable();
            $table->foreignId('rembourse_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('relance_at')->nullable();
            $table->timestamp('notification_at')->nullable();
            $table->text('notification_message')->nullable();

            $table->timestamps();

            $table->index('statut');
            $table->index('beneficiaire_id');
            $table->index(['date_depart', 'date_retour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions_deplacement');
    }
};
