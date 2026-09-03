<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('login_enabled')->default(true)->after('password');
        });

        Schema::table('enseignants', function (Blueprint $table) {
            $table->string('cni')->nullable()->unique()->after('matricule');
            $table->string('type_engagement')->nullable()->index()->after('cni');
            $table->string('source_import')->nullable()->after('type_engagement');
            $table->string('source_reference', 20)->nullable()->after('source_import');
            $table->timestamp('imported_at')->nullable()->after('source_reference');
        });

        Schema::create('teacher_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_name');
            $table->char('source_sha256', 64)->unique();
            $table->string('source_reference', 20)->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->unsignedInteger('source_rows')->default(0);
            $table->unsignedInteger('created_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('unchanged_rows')->default(0);
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_import_batches');

        Schema::table('enseignants', function (Blueprint $table) {
            $table->dropUnique(['cni']);
            $table->dropIndex(['type_engagement']);
            $table->dropColumn([
                'cni',
                'type_engagement',
                'source_import',
                'source_reference',
                'imported_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('login_enabled');
        });
    }
};
