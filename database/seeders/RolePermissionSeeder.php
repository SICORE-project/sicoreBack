<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('slug', 'administrateur')->first();

        $permissions = Permission::all()->pluck('id');

        $admin->permissions()->sync($permissions);
    }
}