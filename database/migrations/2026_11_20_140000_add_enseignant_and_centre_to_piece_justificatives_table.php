<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une piece justificative appartient a un membre (enseignant) d'un centre
 * precis d'une convocation : le modele piece_justificatives (fillable,
 * relations enseignant()/centre()) et le depot les utilisent, mais aucune
 * migration ne creait ces colonnes -> "Champ 'enseignant_id' inconnu" a
 * l'ajout d'une piece.
 *
 * Pas de contrainte de cle etrangere : la table est en MyISAM (qui les
 * ignore) ; de simples index, comme pour les autres liaisons de la table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('piece_justificatives', function (Blueprint $table) {
            if (! Schema::hasColumn('piece_justificatives', 'enseignant_id')) {
                $table->unsignedBigInteger('enseignant_id')->nullable()->after('convocation_id');
                $table->index('enseignant_id');
            }

            if (! Schema::hasColumn('piece_justificatives', 'centre_id')) {
                $table->unsignedBigInteger('centre_id')->nullable()->after('enseignant_id');
                $table->index('centre_id');
            }
        });

        // Recherche d'une piece par (convocation, membre, type) a chaque depot.
        if (! $this->indexExiste('piece_justificatives', 'piece_justificatives_conv_ens_type_index')) {
            Schema::table('piece_justificatives', function (Blueprint $table) {
                $table->index(['convocation_id', 'enseignant_id', 'type'], 'piece_justificatives_conv_ens_type_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('piece_justificatives', function (Blueprint $table) {
            if ($this->indexExiste('piece_justificatives', 'piece_justificatives_conv_ens_type_index')) {
                $table->dropIndex('piece_justificatives_conv_ens_type_index');
            }

            if (Schema::hasColumn('piece_justificatives', 'centre_id')) {
                $table->dropIndex(['centre_id']);
                $table->dropColumn('centre_id');
            }

            if (Schema::hasColumn('piece_justificatives', 'enseignant_id')) {
                $table->dropIndex(['enseignant_id']);
                $table->dropColumn('enseignant_id');
            }
        });
    }

    private function indexExiste(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $i) => $i['name'] === $index);
    }
};
