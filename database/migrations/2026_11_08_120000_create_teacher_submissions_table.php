<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ia_id')->constrained('ias');
            $table->foreignId('submitted_by')->constrained('users');
            $table->json('payload');
            $table->string('cni', 15)->unique();
            $table->string('matricule', 20)->unique();
            $table->string('rib', 24)->unique();
            $table->string('email', 100)->nullable()->unique();
            $table->string('status')->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('enseignant_id')->nullable()->constrained('enseignants');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('teacher_submissions'); }
};
