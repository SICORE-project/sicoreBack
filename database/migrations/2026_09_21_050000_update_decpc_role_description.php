<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DESCRIPTION = 'Planification, organisation et supervision des examens, concours professionnels et certifications (CAP, BEP, BT, BTS et CPS), ainsi que délivrance des diplômes et attestations correspondants';

    public function up(): void
    {
        DB::table('roles')
            ->where('slug', 'decpc')
            ->update([
                'description' => self::DESCRIPTION,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('slug', 'decpc')
            ->update([
                'description' => 'Direction des Examens, Concours Professionnels et Certifications',
                'updated_at' => now(),
            ]);
    }
};
