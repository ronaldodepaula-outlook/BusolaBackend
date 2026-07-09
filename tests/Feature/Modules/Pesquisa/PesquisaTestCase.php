<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Modules\Pesquisa\Concerns\AutenticaComoJwt;
use Tests\TestCase;

abstract class PesquisaTestCase extends TestCase
{
    use RefreshDatabase;
    use AutenticaComoJwt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    protected function criarSuperAdmin(): User
    {
        return User::factory()->create(['tipo' => 'superadmin', 'empresa_id' => null]);
    }

    protected function criarUsuarioComPermissoes(Empresa $empresa, array $slugs): User
    {
        $user = User::factory()->create(['tipo' => 'usuario', 'empresa_id' => $empresa->id]);

        if (! empty($slugs)) {
            $role = Role::create([
                'empresa_id' => $empresa->id,
                'nome'       => 'Papel de Teste '.uniqid(),
                'status'     => 'ativo',
                'sistema'    => false,
            ]);

            $role->permissions()->sync(Permission::whereIn('slug', $slugs)->pluck('id'));
            $user->roles()->attach($role->id);
        }

        return $user->fresh();
    }

    protected function todasPermissoesDoModulo(): array
    {
        return [
            'formulario.listar', 'formulario.criar', 'formulario.visualizar', 'formulario.editar', 'formulario.excluir', 'formulario.versionar',
            'categoria.listar', 'categoria.criar', 'categoria.visualizar', 'categoria.editar', 'categoria.excluir',
            'subcategoria.listar', 'subcategoria.criar', 'subcategoria.visualizar', 'subcategoria.editar', 'subcategoria.excluir',
            'pergunta.listar', 'pergunta.criar', 'pergunta.visualizar', 'pergunta.editar', 'pergunta.excluir',
            'conceito.listar', 'conceito.criar', 'conceito.visualizar', 'conceito.editar', 'conceito.excluir',
            'pesquisa.listar', 'pesquisa.criar', 'pesquisa.visualizar', 'pesquisa.editar', 'pesquisa.excluir', 'pesquisa.publicar', 'pesquisa.encerrar',
            'resultado.consultar',
            'setor.listar', 'setor.criar', 'setor.editar', 'setor.excluir',
            'ghe.listar', 'ghe.criar', 'ghe.editar', 'ghe.excluir',
            'plano_acao.gerar', 'plano_acao.editar',
            'relatorio.gerar', 'relatorio.listar', 'relatorio.listar_todas',
            'colaborador.listar', 'colaborador.criar', 'colaborador.editar', 'colaborador.excluir',
            'colaborador.importar', 'colaborador.visualizar_dados_sensiveis',
        ];
    }
}
