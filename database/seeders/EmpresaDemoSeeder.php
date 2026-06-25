<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Filial;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmpresaDemoSeeder extends Seeder
{
    /**
     * Create a demo company, its main branch and an admin user for testing.
     * Safe to re-run — all records are resolved via updateOrCreate.
     */
    public function run(): void
    {
        // ── 1. Empresa Demo ───────────────────────────────────────────────────
        $empresa = Empresa::updateOrCreate(
            ['cnpj' => '12.345.678/0001-90'],
            [
                'nome'         => 'Empresa Demo',
                'email'        => 'demo@empresa.com',
                'status'       => 'ativo',
                'plano'        => 'professional',
                'max_filiais'  => 10,
                'max_usuarios' => 100,
            ]
        );

        // ── 2. Filial Principal ───────────────────────────────────────────────
        $filial = Filial::updateOrCreate(
            [
                'empresa_id' => $empresa->id,
                'codigo'     => 'FIL001',
            ],
            [
                'nome'        => 'Filial Principal',
                'status'      => 'ativo',
                'responsavel' => 'Admin Demo',
            ]
        );

        // ── 3. Usuário Administrador Demo ─────────────────────────────────────
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@empresa.com'],
            [
                'empresa_id'     => $empresa->id,
                'filial_id'      => $filial->id,
                'nome'           => 'Administrador Demo',
                'senha'          => Hash::make('Admin@2024'),
                'tipo'           => 'admin',
                'status'         => 'ativo',
                'primeiro_acesso' => false,
            ]
        );

        // Attach admin role (sync keeps pivot table clean on re-runs)
        $role = Role::where('slug', 'admin')->first();

        if ($role) {
            $adminUser->roles()->sync([$role->id]);
        } else {
            $this->command->warn('EmpresaDemoSeeder: role "admin" não encontrada — execute RoleSeeder primeiro.');
        }

        $this->command->info("EmpresaDemoSeeder: empresa \"{$empresa->nome}\" (ID {$empresa->id}), filial \"{$filial->nome}\" (ID {$filial->id}) e usuário admin@empresa.com (ID {$adminUser->id}) criados/atualizados.");
    }
}
