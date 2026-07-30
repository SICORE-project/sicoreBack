<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This foreign key is consolidated in the 2026_07_29_100558 migration
        // together with the other users foreign keys.
    }

    public function down(): void
    {
        // See up().
    }
};
