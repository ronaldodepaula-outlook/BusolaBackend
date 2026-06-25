<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters:
     *   1. PermissionSeeder  — create all system permissions first
     *   2. RoleSeeder        — create roles and attach permissions
     *   3. SuperAdminSeeder  — create the global super-admin user
     *   4. EmpresaDemoSeeder — create demo company, branch and admin user
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            SuperAdminSeeder::class,
            EmpresaDemoSeeder::class,
        ]);
    }
}
