<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lieu_de_services', 'telephone')) {
            Schema::table('lieu_de_services', function (Blueprint $table): void {
                $table->string('telephone', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Cette colonne peut préexister à la migration ; conserver les numéros enregistrés.
    }
};
