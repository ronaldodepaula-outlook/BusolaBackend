<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create (or update) the global super administrator user and attach the
     * superadmin role.  Safe to re-run — uses updateOrCreate on email.
     */
    public function run(): void
    {
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@sistema.com'],
            [
                'empresa_id'    => null,
                'filial_id'     => null,
                'nome'          => 'Super Administrador',
                'senha'         => Hash::make('Admin@2024'),
                'tipo'          => 'superadmin',
                'status'        => 'ativo',
                'primeiro_acesso' => false,
            ]
        );

        // Attach superadmin role (sync keeps the pivot table clean on re-runs)
        $role = Role::where('slug', 'superadmin')->first();

        if ($role) {
            $superadmin->roles()->sync([$role->id]);
        } else {
            $this->command->warn('SuperAdminSeeder: role "superadmin" não encontrada — execute RoleSeeder primeiro.');
        }

        $this->command->info("SuperAdminSeeder: usuário superadmin@sistema.com criado/atualizado (ID {$superadmin->id}).");
    }
}
