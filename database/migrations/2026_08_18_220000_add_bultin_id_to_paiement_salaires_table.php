<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->foreignId('bultin_id')->nullable()->after('delegation_credit_id')
                  ->constrained('bultins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->dropForeign(['bultin_id']);
            $table->dropColumn('bultin_id');
        });
    }
};
