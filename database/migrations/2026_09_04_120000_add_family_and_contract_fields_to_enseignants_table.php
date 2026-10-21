<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table): void {
            $table->boolean('est_en_couple')->default(false)->after('situation_familiale_id');
            $table->decimal('nombre_parts_fiscales', 3, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table): void {
            $table->dropColumn('est_en_couple');
            $table->integer('nombre_parts_fiscales')->nullable()->change();
        });
    }
};
