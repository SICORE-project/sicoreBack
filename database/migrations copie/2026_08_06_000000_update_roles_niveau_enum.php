<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            DB::statement("UPDATE `roles` SET `niveau` = 'admin' WHERE `niveau` = 'admin_metier'");
            DB::statement("UPDATE `roles` SET `niveau` = 'gestion' WHERE `niveau` = 'gestionnaire'");
            DB::statement("ALTER TABLE `roles` MODIFY `niveau` ENUM('systeme','admin','gestion','consultation') NOT NULL DEFAULT 'consultation'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            DB::statement("UPDATE `roles` SET `niveau` = 'admin_metier' WHERE `niveau` = 'admin'");
            DB::statement("UPDATE `roles` SET `niveau` = 'gestionnaire' WHERE `niveau` = 'gestion'");
            DB::statement("ALTER TABLE `roles` MODIFY `niveau` ENUM('systeme','admin_metier','gestionnaire','consultation') NOT NULL DEFAULT 'consultation'");
        }
    }
};
