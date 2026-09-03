<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('piece_justificatives', 'enseignant_id')) {
            return;
        }

        Schema::table('piece_justificatives', function (Blueprint $table) {
            $table->foreignId('enseignant_id')->nullable()->after('convocation_id')
                ->constrained('enseignants')->nullOnDelete();

            $table->foreignId('centre_id')->nullable()->after('enseignant_id')
                ->constrained('convocation_centres')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('piece_justificatives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enseignant_id');
            $table->dropConstrainedForeignId('centre_id');
        });
    }
};
