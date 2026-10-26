<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocalSetupSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            IaSeeder::class,
            IefSeeder::class,
            AdminStructureSeeder::class,
            DecpcStructureSeeder::class,
            TypeRoleSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            AgentDrhSeeder::class,
            AgentDecpcSeeder::class,
            RecruitmentPermissionSeeder::class,
            UserSeeder::class,
        ]);
        if (! \Illuminate\Support\Facades\DB::table('types_convocation')->where('code', 'CORRECTION')->exists()) {
            $this->call(TypeConvocationSeeder::class);
        }
    }
}
