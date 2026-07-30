<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foreign keys are added by the 2026_07_29_100558 migration, after all
        // referenced tables have been created.
    }

    public function down(): void
    {
        // See up().
    }
};
