<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed system roles (empresa_id = null, sistema = true) and attach permissions.
     * Uses updateOrCreate for full idempotence — safe to re-run at any time.
     */
    public function run(): void
    {
        // ── Helper: resolve permission IDs by slugs ───────────────────────────
        $permIds = fn (array $slugs): array =>
            Permission::whereIn('slug', $slugs)->pluck('id')->toArray();

        // All permission slugs — used by Super Administrador
        $allSlugs = Permission::pluck('slug')->toArray();

        // ── 1. Super Administrador ─────────────────────────────────────────────
        $superadmin = Role::updateOrCreate(
            ['slug' => 'superadmin'],
            [
                'empresa_id' => null,
                'nome'       => 'Super Administrador',
                'descricao'  => 'Acesso irrestrito a todos os recursos e configurações do sistema.',
                'status'     => 'ativo',
                'sistema'    => true,
            ]
        );

        $superadmin->permissions()->sync($permIds($allSlugs));

        // ── 2. Administrador ──────────────────────────────────────────────────
        //    All permissions EXCEPT:
        //      empresa.excluir | permissao.criar/editar/excluir |
        //      dashboard.superadmin | log.exportar | relatorio.listar_todas
        $adminExcluded = [
            'empresa.excluir',
            'permissao.criar',
            'permissao.editar',
            'permissao.excluir',
            'dashboard.superadmin',
            'log.exportar',
            'relatorio.listar_todas',
        ];

        $adminSlugs = array_values(
            array_filter($allSlugs, fn ($s) => ! in_array($s, $adminExcluded, true))
        );

        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'empresa_id' => null,
                'nome'       => 'Administrador',
                'descricao'  => 'Administrador da empresa com acesso completo, exceto operações críticas reservadas ao super administrador.',
                'status'     => 'ativo',
                'sistema'    => true,
            ]
        );

        $admin->permissions()->sync($permIds($adminSlugs));

        // ── 3. Gerente ────────────────────────────────────────────────────────
        $gerenteSlugs = [
            'usuario.listar',
            'usuario.criar',
            'usuario.visualizar',
            'usuario.editar',
            'usuario.bloquear',
            'filial.listar',
            'filial.visualizar',
            'role.listar',
            'role.visualizar',
            'dashboard.empresa',
            'log.listar',
            'log.visualizar',
            'formulario.listar',
            'formulario.visualizar',
            'categoria.listar',
            'categoria.visualizar',
            'subcategoria.listar',
            'subcategoria.visualizar',
            'pergunta.listar',
            'pergunta.visualizar',
            'conceito.listar',
            'conceito.visualizar',
            'pesquisa.listar',
            'pesquisa.visualizar',
            'resultado.consultar',
            'setor.listar',
            'ghe.listar',
            'colaborador.listar',
            'relatorio.listar',
        ];

        $gerente = Role::updateOrCreate(
            ['slug' => 'gerente'],
            [
                'empresa_id' => null,
                'nome'       => 'Gerente',
                'descricao'  => 'Gerente operacional com acesso à gestão de usuários, consulta de filiais/perfis e visualização de logs.',
                'status'     => 'ativo',
                'sistema'    => true,
            ]
        );

        $gerente->permissions()->sync($permIds($gerenteSlugs));

        // ── 4. Operador ───────────────────────────────────────────────────────
        $operadorSlugs = [
            'usuario.listar',
            'usuario.visualizar',
            'filial.listar',
            'filial.visualizar',
            'dashboard.empresa',
        ];

        $operador = Role::updateOrCreate(
            ['slug' => 'operador'],
            [
                'empresa_id' => null,
                'nome'       => 'Operador',
                'descricao'  => 'Operador com acesso somente leitura a usuários e filiais.',
                'status'     => 'ativo',
                'sistema'    => true,
            ]
        );

        $operador->permissions()->sync($permIds($operadorSlugs));

        $this->command->info('RoleSeeder: 4 roles de sistema criadas/atualizadas com suas permissões.');
    }
}
