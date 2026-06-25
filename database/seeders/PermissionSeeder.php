<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Seed all system permissions, organized by module.
     * Uses updateOrCreate for full idempotence — safe to re-run at any time.
     */
    public function run(): void
    {
        $permissions = [

            // ── Empresa ──────────────────────────────────────────────────────────
            [
                'nome'     => 'Listar Empresas',
                'slug'     => 'empresa.listar',
                'modulo'   => 'empresa',
                'descricao' => 'Permite listar todas as empresas cadastradas no sistema.',
            ],
            [
                'nome'     => 'Criar Empresa',
                'slug'     => 'empresa.criar',
                'modulo'   => 'empresa',
                'descricao' => 'Permite cadastrar uma nova empresa no sistema.',
            ],
            [
                'nome'     => 'Visualizar Empresa',
                'slug'     => 'empresa.visualizar',
                'modulo'   => 'empresa',
                'descricao' => 'Permite visualizar os detalhes de uma empresa.',
            ],
            [
                'nome'     => 'Editar Empresa',
                'slug'     => 'empresa.editar',
                'modulo'   => 'empresa',
                'descricao' => 'Permite editar os dados de uma empresa.',
            ],
            [
                'nome'     => 'Excluir Empresa',
                'slug'     => 'empresa.excluir',
                'modulo'   => 'empresa',
                'descricao' => 'Permite excluir uma empresa do sistema.',
            ],

            // ── Filial ────────────────────────────────────────────────────────────
            [
                'nome'     => 'Listar Filiais',
                'slug'     => 'filial.listar',
                'modulo'   => 'filial',
                'descricao' => 'Permite listar as filiais de uma empresa.',
            ],
            [
                'nome'     => 'Criar Filial',
                'slug'     => 'filial.criar',
                'modulo'   => 'filial',
                'descricao' => 'Permite cadastrar uma nova filial.',
            ],
            [
                'nome'     => 'Visualizar Filial',
                'slug'     => 'filial.visualizar',
                'modulo'   => 'filial',
                'descricao' => 'Permite visualizar os detalhes de uma filial.',
            ],
            [
                'nome'     => 'Editar Filial',
                'slug'     => 'filial.editar',
                'modulo'   => 'filial',
                'descricao' => 'Permite editar os dados de uma filial.',
            ],
            [
                'nome'     => 'Excluir Filial',
                'slug'     => 'filial.excluir',
                'modulo'   => 'filial',
                'descricao' => 'Permite excluir uma filial.',
            ],

            // ── Usuário ───────────────────────────────────────────────────────────
            [
                'nome'     => 'Listar Usuários',
                'slug'     => 'usuario.listar',
                'modulo'   => 'usuario',
                'descricao' => 'Permite listar os usuários da empresa.',
            ],
            [
                'nome'     => 'Criar Usuário',
                'slug'     => 'usuario.criar',
                'modulo'   => 'usuario',
                'descricao' => 'Permite cadastrar um novo usuário.',
            ],
            [
                'nome'     => 'Visualizar Usuário',
                'slug'     => 'usuario.visualizar',
                'modulo'   => 'usuario',
                'descricao' => 'Permite visualizar os detalhes de um usuário.',
            ],
            [
                'nome'     => 'Editar Usuário',
                'slug'     => 'usuario.editar',
                'modulo'   => 'usuario',
                'descricao' => 'Permite editar os dados de um usuário.',
            ],
            [
                'nome'     => 'Excluir Usuário',
                'slug'     => 'usuario.excluir',
                'modulo'   => 'usuario',
                'descricao' => 'Permite excluir um usuário.',
            ],
            [
                'nome'     => 'Bloquear Usuário',
                'slug'     => 'usuario.bloquear',
                'modulo'   => 'usuario',
                'descricao' => 'Permite bloquear ou desbloquear um usuário.',
            ],
            [
                'nome'     => 'Resetar Senha do Usuário',
                'slug'     => 'usuario.resetar-senha',
                'modulo'   => 'usuario',
                'descricao' => 'Permite disparar o reset de senha de um usuário.',
            ],

            // ── Role ──────────────────────────────────────────────────────────────
            [
                'nome'     => 'Listar Perfis',
                'slug'     => 'role.listar',
                'modulo'   => 'role',
                'descricao' => 'Permite listar os perfis de acesso.',
            ],
            [
                'nome'     => 'Criar Perfil',
                'slug'     => 'role.criar',
                'modulo'   => 'role',
                'descricao' => 'Permite criar um novo perfil de acesso.',
            ],
            [
                'nome'     => 'Visualizar Perfil',
                'slug'     => 'role.visualizar',
                'modulo'   => 'role',
                'descricao' => 'Permite visualizar os detalhes de um perfil de acesso.',
            ],
            [
                'nome'     => 'Editar Perfil',
                'slug'     => 'role.editar',
                'modulo'   => 'role',
                'descricao' => 'Permite editar um perfil de acesso.',
            ],
            [
                'nome'     => 'Excluir Perfil',
                'slug'     => 'role.excluir',
                'modulo'   => 'role',
                'descricao' => 'Permite excluir um perfil de acesso.',
            ],

            // ── Permissão ─────────────────────────────────────────────────────────
            [
                'nome'     => 'Criar Permissão',
                'slug'     => 'permissao.criar',
                'modulo'   => 'permissao',
                'descricao' => 'Permite criar novas permissões no sistema.',
            ],
            [
                'nome'     => 'Editar Permissão',
                'slug'     => 'permissao.editar',
                'modulo'   => 'permissao',
                'descricao' => 'Permite editar permissões existentes.',
            ],
            [
                'nome'     => 'Excluir Permissão',
                'slug'     => 'permissao.excluir',
                'modulo'   => 'permissao',
                'descricao' => 'Permite excluir permissões do sistema.',
            ],

            // ── Dashboard ─────────────────────────────────────────────────────────
            [
                'nome'     => 'Dashboard Super Administrador',
                'slug'     => 'dashboard.superadmin',
                'modulo'   => 'dashboard',
                'descricao' => 'Acesso ao painel de controle global do super administrador.',
            ],
            [
                'nome'     => 'Dashboard Empresa',
                'slug'     => 'dashboard.empresa',
                'modulo'   => 'dashboard',
                'descricao' => 'Acesso ao painel de controle da empresa.',
            ],

            // ── Log ───────────────────────────────────────────────────────────────
            [
                'nome'     => 'Listar Logs',
                'slug'     => 'log.listar',
                'modulo'   => 'log',
                'descricao' => 'Permite listar os registros de auditoria.',
            ],
            [
                'nome'     => 'Visualizar Log',
                'slug'     => 'log.visualizar',
                'modulo'   => 'log',
                'descricao' => 'Permite visualizar o detalhe de um registro de auditoria.',
            ],
            [
                'nome'     => 'Exportar Logs',
                'slug'     => 'log.exportar',
                'modulo'   => 'log',
                'descricao' => 'Permite exportar os registros de auditoria.',
            ],

            // ── Configuração ──────────────────────────────────────────────────────
            [
                'nome'     => 'Editar Configurações',
                'slug'     => 'configuracao.editar',
                'modulo'   => 'configuracao',
                'descricao' => 'Permite editar as configurações da empresa.',
            ],

            // ── Perfil do Usuário ─────────────────────────────────────────────────
            [
                'nome'     => 'Editar Próprio Perfil',
                'slug'     => 'perfil.editar',
                'modulo'   => 'perfil',
                'descricao' => 'Permite ao usuário editar seu próprio perfil.',
            ],
        ];

        foreach ($permissions as $data) {
            Permission::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'nome'     => $data['nome'],
                    'modulo'   => $data['modulo'],
                    'descricao' => $data['descricao'],
                ]
            );
        }

        $this->command->info('PermissionSeeder: ' . count($permissions) . ' permissões criadas/atualizadas.');
    }
}
