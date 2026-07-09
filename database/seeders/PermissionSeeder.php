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

            // ── Pesquisa Psicossocial: Formulário ───────────────────────────────────
            [
                'nome'     => 'Listar Formulários',
                'slug'     => 'formulario.listar',
                'modulo'   => 'formulario',
                'descricao' => 'Permite listar os formulários de pesquisa psicossocial.',
            ],
            [
                'nome'     => 'Criar Formulário',
                'slug'     => 'formulario.criar',
                'modulo'   => 'formulario',
                'descricao' => 'Permite criar um novo formulário de pesquisa psicossocial.',
            ],
            [
                'nome'     => 'Visualizar Formulário',
                'slug'     => 'formulario.visualizar',
                'modulo'   => 'formulario',
                'descricao' => 'Permite visualizar os detalhes de um formulário de pesquisa psicossocial.',
            ],
            [
                'nome'     => 'Editar Formulário',
                'slug'     => 'formulario.editar',
                'modulo'   => 'formulario',
                'descricao' => 'Permite editar um formulário de pesquisa psicossocial.',
            ],
            [
                'nome'     => 'Excluir Formulário',
                'slug'     => 'formulario.excluir',
                'modulo'   => 'formulario',
                'descricao' => 'Permite excluir um formulário de pesquisa psicossocial.',
            ],
            [
                'nome'     => 'Versionar Formulário',
                'slug'     => 'formulario.versionar',
                'modulo'   => 'formulario',
                'descricao' => 'Permite forçar a criação manual de uma nova versão de um formulário.',
            ],

            // ── Pesquisa Psicossocial: Categoria ─────────────────────────────────────
            [
                'nome'     => 'Listar Categorias',
                'slug'     => 'categoria.listar',
                'modulo'   => 'categoria',
                'descricao' => 'Permite listar as categorias de um formulário de pesquisa psicossocial.',
            ],
            [
                'nome'     => 'Criar Categoria',
                'slug'     => 'categoria.criar',
                'modulo'   => 'categoria',
                'descricao' => 'Permite criar uma nova categoria em um formulário de pesquisa psicossocial.',
            ],
            [
                'nome'     => 'Visualizar Categoria',
                'slug'     => 'categoria.visualizar',
                'modulo'   => 'categoria',
                'descricao' => 'Permite visualizar os detalhes de uma categoria.',
            ],
            [
                'nome'     => 'Editar Categoria',
                'slug'     => 'categoria.editar',
                'modulo'   => 'categoria',
                'descricao' => 'Permite editar uma categoria.',
            ],
            [
                'nome'     => 'Excluir Categoria',
                'slug'     => 'categoria.excluir',
                'modulo'   => 'categoria',
                'descricao' => 'Permite excluir uma categoria.',
            ],

            // ── Pesquisa Psicossocial: Subcategoria ──────────────────────────────────
            [
                'nome'     => 'Listar Subcategorias',
                'slug'     => 'subcategoria.listar',
                'modulo'   => 'subcategoria',
                'descricao' => 'Permite listar as subcategorias de uma categoria.',
            ],
            [
                'nome'     => 'Criar Subcategoria',
                'slug'     => 'subcategoria.criar',
                'modulo'   => 'subcategoria',
                'descricao' => 'Permite criar uma nova subcategoria.',
            ],
            [
                'nome'     => 'Visualizar Subcategoria',
                'slug'     => 'subcategoria.visualizar',
                'modulo'   => 'subcategoria',
                'descricao' => 'Permite visualizar os detalhes de uma subcategoria.',
            ],
            [
                'nome'     => 'Editar Subcategoria',
                'slug'     => 'subcategoria.editar',
                'modulo'   => 'subcategoria',
                'descricao' => 'Permite editar uma subcategoria.',
            ],
            [
                'nome'     => 'Excluir Subcategoria',
                'slug'     => 'subcategoria.excluir',
                'modulo'   => 'subcategoria',
                'descricao' => 'Permite excluir uma subcategoria.',
            ],

            // ── Pesquisa Psicossocial: Pergunta ───────────────────────────────────────
            [
                'nome'     => 'Listar Perguntas',
                'slug'     => 'pergunta.listar',
                'modulo'   => 'pergunta',
                'descricao' => 'Permite listar as perguntas de uma subcategoria.',
            ],
            [
                'nome'     => 'Criar Pergunta',
                'slug'     => 'pergunta.criar',
                'modulo'   => 'pergunta',
                'descricao' => 'Permite criar uma nova pergunta.',
            ],
            [
                'nome'     => 'Visualizar Pergunta',
                'slug'     => 'pergunta.visualizar',
                'modulo'   => 'pergunta',
                'descricao' => 'Permite visualizar os detalhes de uma pergunta.',
            ],
            [
                'nome'     => 'Editar Pergunta',
                'slug'     => 'pergunta.editar',
                'modulo'   => 'pergunta',
                'descricao' => 'Permite editar uma pergunta.',
            ],
            [
                'nome'     => 'Excluir Pergunta',
                'slug'     => 'pergunta.excluir',
                'modulo'   => 'pergunta',
                'descricao' => 'Permite excluir uma pergunta.',
            ],

            // ── Pesquisa Psicossocial: Conceito de Avaliação ─────────────────────────
            [
                'nome'     => 'Listar Conceitos',
                'slug'     => 'conceito.listar',
                'modulo'   => 'conceito',
                'descricao' => 'Permite listar os conceitos de avaliação (escalas).',
            ],
            [
                'nome'     => 'Criar Conceito',
                'slug'     => 'conceito.criar',
                'modulo'   => 'conceito',
                'descricao' => 'Permite criar um novo conceito de avaliação.',
            ],
            [
                'nome'     => 'Visualizar Conceito',
                'slug'     => 'conceito.visualizar',
                'modulo'   => 'conceito',
                'descricao' => 'Permite visualizar os detalhes de um conceito de avaliação.',
            ],
            [
                'nome'     => 'Editar Conceito',
                'slug'     => 'conceito.editar',
                'modulo'   => 'conceito',
                'descricao' => 'Permite editar um conceito de avaliação e seus itens.',
            ],
            [
                'nome'     => 'Excluir Conceito',
                'slug'     => 'conceito.excluir',
                'modulo'   => 'conceito',
                'descricao' => 'Permite excluir um conceito de avaliação.',
            ],

            // ── Pesquisa Psicossocial: Campanha (Pesquisa) ───────────────────────────
            [
                'nome'     => 'Listar Campanhas',
                'slug'     => 'pesquisa.listar',
                'modulo'   => 'pesquisa',
                'descricao' => 'Permite listar as campanhas de pesquisa psicossocial.',
            ],
            [
                'nome'     => 'Criar Campanha',
                'slug'     => 'pesquisa.criar',
                'modulo'   => 'pesquisa',
                'descricao' => 'Permite criar uma nova campanha de pesquisa.',
            ],
            [
                'nome'     => 'Visualizar Campanha',
                'slug'     => 'pesquisa.visualizar',
                'modulo'   => 'pesquisa',
                'descricao' => 'Permite visualizar os detalhes de uma campanha.',
            ],
            [
                'nome'     => 'Editar Campanha',
                'slug'     => 'pesquisa.editar',
                'modulo'   => 'pesquisa',
                'descricao' => 'Permite editar os dados e o público-alvo de uma campanha em rascunho.',
            ],
            [
                'nome'     => 'Excluir Campanha',
                'slug'     => 'pesquisa.excluir',
                'modulo'   => 'pesquisa',
                'descricao' => 'Permite excluir uma campanha em rascunho.',
            ],
            [
                'nome'     => 'Publicar Campanha',
                'slug'     => 'pesquisa.publicar',
                'modulo'   => 'pesquisa',
                'descricao' => 'Permite publicar uma campanha, tornando-a ativa.',
            ],
            [
                'nome'     => 'Encerrar Campanha',
                'slug'     => 'pesquisa.encerrar',
                'modulo'   => 'pesquisa',
                'descricao' => 'Permite encerrar uma campanha ativa.',
            ],

            // ── Pesquisa Psicossocial: Resultados ────────────────────────────────
            [
                'nome'     => 'Consultar Resultados',
                'slug'     => 'resultado.consultar',
                'modulo'   => 'resultado',
                'descricao' => 'Permite consultar a tabulação agregada de resultados de uma campanha, incluindo a classificação de risco por categoria/GHE.',
            ],

            // ── Pesquisa Psicossocial: Colaboradores (LGPD) ──────────────────────
            [
                'nome'     => 'Listar Colaboradores',
                'slug'     => 'colaborador.listar',
                'modulo'   => 'colaborador',
                'descricao' => 'Permite listar os colaboradores da empresa (dados sensíveis sempre mascarados nesta listagem).',
            ],
            [
                'nome'     => 'Cadastrar Colaborador',
                'slug'     => 'colaborador.criar',
                'modulo'   => 'colaborador',
                'descricao' => 'Permite cadastrar manualmente um novo colaborador.',
            ],
            [
                'nome'     => 'Editar Colaborador',
                'slug'     => 'colaborador.editar',
                'modulo'   => 'colaborador',
                'descricao' => 'Permite editar os dados de um colaborador.',
            ],
            [
                'nome'     => 'Excluir Colaborador',
                'slug'     => 'colaborador.excluir',
                'modulo'   => 'colaborador',
                'descricao' => 'Permite excluir ou anonimizar os dados pessoais de um colaborador.',
            ],
            [
                'nome'     => 'Importar Colaboradores (CSV)',
                'slug'     => 'colaborador.importar',
                'modulo'   => 'colaborador',
                'descricao' => 'Permite importar colaboradores em massa via arquivo CSV.',
            ],
            [
                'nome'     => 'Visualizar Dados Sensíveis do Colaborador',
                'slug'     => 'colaborador.visualizar_dados_sensiveis',
                'modulo'   => 'colaborador',
                'descricao' => 'Permite visualizar CPF e data de nascimento em claro — cada acesso fica registrado no log de auditoria do sistema.',
            ],

            // ── Pesquisa Psicossocial: Setores e GHE ─────────────────────────────
            [
                'nome'     => 'Listar Setores',
                'slug'     => 'setor.listar',
                'modulo'   => 'setor',
                'descricao' => 'Permite listar os setores organizacionais da empresa.',
            ],
            [
                'nome'     => 'Criar Setor',
                'slug'     => 'setor.criar',
                'modulo'   => 'setor',
                'descricao' => 'Permite criar um novo setor.',
            ],
            [
                'nome'     => 'Editar Setor',
                'slug'     => 'setor.editar',
                'modulo'   => 'setor',
                'descricao' => 'Permite editar um setor, incluindo o GHE ao qual pertence e o colaborador vinculado.',
            ],
            [
                'nome'     => 'Excluir Setor',
                'slug'     => 'setor.excluir',
                'modulo'   => 'setor',
                'descricao' => 'Permite excluir um setor.',
            ],
            [
                'nome'     => 'Listar GHEs',
                'slug'     => 'ghe.listar',
                'modulo'   => 'ghe',
                'descricao' => 'Permite listar os Grupos Homogêneos de Exposição (GHE) da empresa.',
            ],
            [
                'nome'     => 'Criar GHE',
                'slug'     => 'ghe.criar',
                'modulo'   => 'ghe',
                'descricao' => 'Permite criar um novo GHE.',
            ],
            [
                'nome'     => 'Editar GHE',
                'slug'     => 'ghe.editar',
                'modulo'   => 'ghe',
                'descricao' => 'Permite editar um GHE.',
            ],
            [
                'nome'     => 'Excluir GHE',
                'slug'     => 'ghe.excluir',
                'modulo'   => 'ghe',
                'descricao' => 'Permite excluir um GHE.',
            ],

            // ── Pesquisa Psicossocial: Plano de Ação ─────────────────────────────
            [
                'nome'     => 'Gerar Plano de Ação',
                'slug'     => 'plano_acao.gerar',
                'modulo'   => 'plano_acao',
                'descricao' => 'Permite (re)gerar o plano de ação de uma campanha a partir da classificação de risco atual.',
            ],
            [
                'nome'     => 'Editar Ação do Plano',
                'slug'     => 'plano_acao.editar',
                'modulo'   => 'plano_acao',
                'descricao' => 'Permite atualizar responsável, prazo, status e observações de uma ação do plano.',
            ],

            // ── Pesquisa Psicossocial: Relatório Técnico ─────────────────────────
            [
                'nome'     => 'Gerar Relatório Técnico',
                'slug'     => 'relatorio.gerar',
                'modulo'   => 'relatorio',
                'descricao' => 'Permite gerar o Relatório Técnico (PDF) de uma campanha.',
            ],
            [
                'nome'     => 'Listar Relatórios Técnicos',
                'slug'     => 'relatorio.listar',
                'modulo'   => 'relatorio',
                'descricao' => 'Permite listar e baixar os relatórios técnicos já gerados da própria empresa.',
            ],
            [
                'nome'     => 'Listar Relatórios Técnicos de Todas as Empresas',
                'slug'     => 'relatorio.listar_todas',
                'modulo'   => 'relatorio',
                'descricao' => 'Permite listar os relatórios técnicos gerados em todas as empresas (gestão do super administrador).',
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
