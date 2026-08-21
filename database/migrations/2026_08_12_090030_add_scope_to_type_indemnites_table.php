<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rend un type_indemnite (barème) "scopable" par type de convocation et/ou
 * catégorie de personnel.
 *
 * Les deux colonnes sont NULLABLES et NULL = joker ("s'applique à tous").
 * Ça permet de garder tel quel un barème générique existant tout en
 * introduisant progressivement des barèmes spécifiques (ex: un barème
 * "jury_examen + fonctionnaire" plus précis qu'un barème générique
 * "jury_examen" tout court).
 *
 * Doit être migrée après create_types_convocation_table (FK) et après
 * add_categorie_personnel_to_enseignants_table (pour rester cohérent sur
 * les valeurs de l'enum, dupliqué ici volontairement : cette colonne ne
 * qualifie pas un enseignant mais un barème, donc pas de FK vers enseignants).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('type_indemnites', 'type_convocation_id')) {
            return;
        }

        Schema::table('type_indemnites', function (Blueprint $table) {
            $table->foreignId('type_convocation_id')
                ->nullable()
                ->after('id')
                ->constrained('types_convocation')
                ->nullOnDelete();

            $table->enum('categorie_personnel', ['vacataire', 'contractuel', 'fonctionnaire'])
                ->nullable()
                ->after('type_convocation_id');

            $table->index(['type_convocation_id', 'categorie_personnel'], 'type_indemnites_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('type_indemnites', function (Blueprint $table) {
            $table->dropIndex('type_indemnites_scope_index');
            $table->dropForeign(['type_convocation_id']);
            $table->dropColumn(['type_convocation_id', 'categorie_personnel']);
        });
    }
};
