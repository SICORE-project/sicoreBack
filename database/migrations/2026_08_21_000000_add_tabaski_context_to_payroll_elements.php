<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_elements', function (Blueprint $table): void {
            $table->foreignId('annee_academique_id')
                ->nullable()
                ->after('academic_year')
                ->constrained('annee_academiques')
                ->nullOnDelete();
            $table->foreignId('application_corps_id')
                ->nullable()
                ->after('application_reference')
                ->constrained('corps_enseignant')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_elements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('application_corps_id');
            $table->dropConstrainedForeignId('annee_academique_id');
        });
    }
};
