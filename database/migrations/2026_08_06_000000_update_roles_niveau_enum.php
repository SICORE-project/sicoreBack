<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            // 1. Mise à jour sécurisée des anciennes valeurs
            DB::table('roles')->where('niveau', 'admin_metier')->update(['niveau' => 'admin']);
            DB::table('roles')->where('niveau', 'gestionnaire')->update(['niveau' => 'gestion']);

            // 2. Modification de l'ENUM via le Schema Builder de Laravel
            Schema::table('roles', function (Blueprint $table) {
                $table->enum('niveau', ['systeme', 'admin', 'gestion', 'consultation'])
                      ->default('consultation')
                      ->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('niveau', 'admin')->update(['niveau' => 'admin_metier']);
            DB::table('roles')->where('niveau', 'gestion')->update(['niveau' => 'gestionnaire']);

            Schema::table('roles', function (Blueprint $table) {
                $table->enum('niveau', ['systeme', 'admin_metier', 'gestionnaire', 'consultation'])
                      ->default('consultation')
                      ->change();
            });
        }
    }
};
