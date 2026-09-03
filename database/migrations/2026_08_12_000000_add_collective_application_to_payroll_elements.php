<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_elements', function (Blueprint $table): void {
            // Conserver le contexte métier de toute application collective.
            $table->string('academic_year', 9)->nullable()->after('amount');
            $table->string('application_scope', 20)->default('individual')->after('academic_year');
            $table->string('application_reference', 100)->nullable()->after('application_scope');
            $table->foreignId('application_ia_id')
                ->nullable()
                ->after('application_reference')
                ->constrained('ias')
                ->nullOnDelete();
            $table->foreignId('application_ief_id')
                ->nullable()
                ->after('application_ia_id')
                ->constrained('iefs')
                ->nullOnDelete();
            $table->timestamp('applied_at')->nullable()->after('application_ief_id');
            $table->foreignId('applied_by')
                ->nullable()
                ->after('applied_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('application_reference', 'payroll_element_application_reference_index');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_elements', function (Blueprint $table): void {
            $table->dropIndex('payroll_element_application_reference_index');
            $table->dropConstrainedForeignId('applied_by');
            $table->dropConstrainedForeignId('application_ief_id');
            $table->dropConstrainedForeignId('application_ia_id');
            $table->dropColumn([
                'academic_year',
                'application_scope',
                'application_reference',
                'applied_at',
            ]);
        });
    }
};
