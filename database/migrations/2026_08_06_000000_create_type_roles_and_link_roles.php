<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('type_roles')) {
            Schema::create('type_roles', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('libelle', 100)->unique();
                $table->string('description', 255)->nullable();
                $table->boolean('est_actif')->default(true);
                $table->timestamps();
            });
        }

        foreach ([
            ['code' => 'systeme', 'libelle' => 'Système'],
            ['code' => 'admin', 'libelle' => 'Administration'],
            ['code' => 'gestion', 'libelle' => 'Gestion'],
            ['code' => 'consultation', 'libelle' => 'Consultation'],
        ] as $typeRole) {
            DB::table('type_roles')->updateOrInsert(
                ['code' => $typeRole['code']],
                $typeRole + ['est_actif' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (! Schema::hasTable('roles')) {
            return;
        }

        if (! Schema::hasColumn('roles', 'type_role_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->foreignId('type_role_id')->nullable()->after('description')->constrained('type_roles')->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('roles', 'niveau')) {
            DB::table('roles')->orderBy('id')->get()->each(function (object $role): void {
                $code = match ($role->niveau) {
                    'admin_metier' => 'admin',
                    'gestionnaire' => 'gestion',
                    'enseignant' => 'consultation',
                    default => $role->niveau,
                };

                $typeRoleId = DB::table('type_roles')->where('code', $code)->value('id')
                    ?? DB::table('type_roles')->where('code', 'consultation')->value('id');

                DB::table('roles')->where('id', $role->id)->update(['type_role_id' => $typeRoleId]);
            });

            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('niveau');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles') && ! Schema::hasColumn('roles', 'niveau')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('niveau', 50)->default('consultation');
            });

            DB::table('roles')->orderBy('id')->get()->each(function (object $role): void {
                $code = DB::table('type_roles')->where('id', $role->type_role_id)->value('code') ?? 'consultation';
                DB::table('roles')->where('id', $role->id)->update(['niveau' => $code]);
            });
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'type_role_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('type_role_id');
            });
        }

        Schema::dropIfExists('type_roles');
    }
};
