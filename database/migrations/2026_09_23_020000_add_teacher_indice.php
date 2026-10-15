<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // La colonne est deja geree par les migrations du 2026_09_22 :
        // creation, conversion en texte et contrainte d'unicite.
    }

    public function down(): void
    {
        // Ne pas supprimer une colonne creee par une migration anterieure.
    }
};
