<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recruitment_batches')) {
            Schema::create('recruitment_batches', function (Blueprint $t) {
                $t->id();
                $t->string('reference')->unique();
                $t->date('recruited_at');
                $t->unsignedBigInteger('created_by');
                $t->string('os_path')->nullable();
                $t->timestamp('transmitted_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('recruitment_members')) {
            Schema::create('recruitment_members', function (Blueprint $t) {
                $t->id();
                $t->foreignId('batch_id')->constrained('recruitment_batches');
                $t->foreignId('enseignant_id')->unique()->constrained('enseignants');
                $t->string('engagement');
                $t->date('engagement_since')->nullable();
                $t->date('service_date')->nullable();
                $t->string('certificate_path')->nullable();
                $t->unsignedBigInteger('service_recorded_by')->nullable();
                $t->timestamp('alerted_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('recruitment_events')) {
            Schema::create('recruitment_events', function (Blueprint $t) {
                $t->id();
                $t->foreignId('batch_id')->constrained('recruitment_batches');
                $t->unsignedBigInteger('member_id')->nullable();
                $t->unsignedBigInteger('user_id');
                $t->string('action');
                $t->string('previous_status')->nullable();
                $t->string('new_status')->nullable();
                $t->date('effective_date')->nullable();
                $t->string('document_path')->nullable();
                $t->timestamp('created_at')->useCurrent();
            });
        }
        if (! Schema::hasTable('recruitment_notices')) {
            Schema::create('recruitment_notices', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->unsignedBigInteger('batch_id');
                $t->string('message', 255);
                $t->timestamp('read_at')->nullable();
                $t->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        foreach (['recruitment_notices', 'recruitment_events', 'recruitment_members', 'recruitment_batches'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
