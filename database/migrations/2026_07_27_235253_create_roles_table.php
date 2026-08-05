<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('nom', 50)->unique();
                $table->string('slug', 50)->unique();
                $table->string('description', 255)->nullable();
                $table->enum('niveau', ['systeme', 'admin_metier', 'gestionnaire', 'consultation'])->default('consultation');
                $table->boolean('est_actif')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['role_id']);
                });
            } catch (\Throwable $e) {
                // ignore if foreign key is already absent
            }
        }

        Schema::dropIfExists('roles');
    }
};