<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('periode_de_paies', function (Blueprint $table) {
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('payroll_period_id')->nullable()->constrained('payroll_periods')->restrictOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('periode_de_paies', function (Blueprint $table) {
            $table->dropForeign(['payroll_period_id']);
            $table->dropColumn(['date_debut', 'date_fin', 'payroll_period_id']);
        });
    }
};
