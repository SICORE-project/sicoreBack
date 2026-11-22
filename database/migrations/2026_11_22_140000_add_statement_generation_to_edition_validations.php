<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une seule génération par mois validé : la première génération de l'État des
 * salaires par l'IA est enregistrée ; ensuite l'écran propose « Consulter ».
 * Une nouvelle validation de l'Édition (fiches modifiées) remet à zéro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_edition_validations', function (Blueprint $table): void {
            $table->timestamp('statement_generated_at')->nullable()->after('total_net');
            $table->foreignId('statement_generated_by')->nullable()->after('statement_generated_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_edition_validations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('statement_generated_by');
            $table->dropColumn('statement_generated_at');
        });
    }
};
