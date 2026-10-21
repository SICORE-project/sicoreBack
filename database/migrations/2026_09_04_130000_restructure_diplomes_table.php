<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diplomes', function (Blueprint $table): void {
            $table->foreignId('categorie_id')->nullable()->after('libelle')->constrained('categories')->nullOnDelete();
            $table->decimal('salaire_brut', 15, 2)->default(0)->after('categorie_id');
            $table->string('code', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('diplomes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('categorie_id');
            $table->dropColumn('salaire_brut');
            $table->string('code', 20)->nullable(false)->change();
        });
    }
};
