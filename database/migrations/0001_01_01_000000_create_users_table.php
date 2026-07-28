<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                // === IDENTITÉ ===
                $table->string('nom', 50);
                $table->string('prenom', 50);
                $table->enum('genre', ['masculin', 'feminin', 'non_precise'])->default('non_precise');
                $table->date('date_naiss')->nullable();
                $table->string('lieu_naissance', 100)->nullable();
                $table->string('telephone', 20)->nullable();
                $table->string('adresse', 255)->nullable();
                $table->string('photo', 255)->nullable();

                // === CONTACT ===
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();

                // === FONCTION ===
                $table->string('fonction', 100)->nullable();
                $table->enum('statut', ['actif', 'inactif', 'bloque'])->default('actif');

                // === SÉCURITÉ ===
                $table->string('password');
                $table->rememberToken();
                $table->boolean('must_change_password')->default(false);
                $table->timestamp('password_changed_at')->nullable();
                $table->integer('tentatives_connexion')->default(0);
                $table->timestamp('verrouille_jusqua')->nullable();
                $table->timestamp('derniere_connexion')->nullable();

                // === CLÉS ÉTRANGÈRES (liens administratifs) ===
                $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
                $table->foreignId('enseignant_id')->nullable()->constrained('enseignants')->onDelete('set null');
                $table->foreignId('lieu_service_id')->nullable()->constrained('lieu_de_services')->onDelete('set null');
                $table->foreignId('ief_id')->nullable()->constrained('iefs')->onDelete('set null');
                $table->foreignId('ia_id')->nullable()->constrained('ias')->onDelete('set null');

                // === TRACABILITÉ ===
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

                $table->timestamps();
                $table->softDeletes(); // Suppression logique (archivage)

                // === INDEX POUR PERFORMANCES ===
                $table->index('email');
                $table->index('statut');
                $table->index('role_id');
                $table->index('enseignant_id');
                $table->index('lieu_service_id');
                $table->index('derniere_connexion');
            });
        }

        // === TABLES SYSTEME LARAVEL ===
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // === TABLE USER_LOGINS (historique des connexions) ===
        if (!Schema::hasTable('user_logins')) {
            Schema::create('user_logins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('login_at');
                $table->timestamp('logout_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->string('session_id')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('login_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logins');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
