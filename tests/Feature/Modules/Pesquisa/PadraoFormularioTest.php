<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Models\PadraoFormulario;

class PadraoFormularioTest extends PesquisaTestCase
{
    public function test_admin_cria_padrao_de_formulario_da_propria_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());

        $resposta = $this->postJson('/api/v1/pesquisa-psicossocial/padroes-formulario', [
            'nome' => 'Padrão Interno XPTO',
            'tipo' => 'empresa',
        ], $this->headersParaUsuario($usuario));

        $resposta->assertStatus(201)
            ->assertJsonPath('dados.nome', 'Padrão Interno XPTO')
            ->assertJsonPath('dados.empresa_id', $empresa->id);
    }

    public function test_admin_comum_nao_pode_criar_padrao_global(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());

        $this->postJson('/api/v1/pesquisa-psicossocial/padroes-formulario', [
            'nome' => 'COPSOQ II',
            'tipo' => 'global',
        ], $this->headersParaUsuario($usuario))->assertStatus(422);
    }

    public function test_superadmin_cria_padrao_global(): void
    {
        $superadmin = $this->criarSuperAdmin();

        $resposta = $this->postJson('/api/v1/pesquisa-psicossocial/padroes-formulario', [
            'nome' => 'COPSOQ II',
            'tipo' => 'global',
        ], $this->headersParaUsuario($superadmin));

        $resposta->assertStatus(201)->assertJsonPath('dados.empresa_id', null);
    }

    public function test_padrao_global_e_visivel_a_todas_as_empresas_mas_padrao_de_empresa_nao(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $usuarioA = $this->criarUsuarioComPermissoes($empresaA, $this->todasPermissoesDoModulo());
        $usuarioB = $this->criarUsuarioComPermissoes($empresaB, $this->todasPermissoesDoModulo());

        PadraoFormulario::factory()->global()->create(['nome' => 'NR-1']);
        PadraoFormulario::factory()->create(['empresa_id' => $empresaA->id, 'nome' => 'Padrão Só da Empresa A']);

        $listaA = $this->getJson('/api/v1/pesquisa-psicossocial/padroes-formulario', $this->headersParaUsuario($usuarioA))
            ->assertStatus(200)->json('dados');
        $listaB = $this->getJson('/api/v1/pesquisa-psicossocial/padroes-formulario', $this->headersParaUsuario($usuarioB))
            ->assertStatus(200)->json('dados');

        $this->assertCount(2, $listaA);
        $this->assertCount(1, $listaB);
        $this->assertEquals('NR-1', $listaB[0]['nome']);
    }

    public function test_nao_permite_nome_duplicado_no_mesmo_escopo(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        PadraoFormulario::factory()->create(['empresa_id' => $empresa->id, 'nome' => 'Padrão Repetido']);

        $this->postJson('/api/v1/pesquisa-psicossocial/padroes-formulario', [
            'nome' => 'Padrão Repetido',
            'tipo' => 'empresa',
        ], $this->headersParaUsuario($usuario))->assertStatus(422);
    }

    public function test_atualiza_nome_descricao_e_status_mas_nao_expoe_edicao_de_escopo(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $padrao = PadraoFormulario::factory()->create(['empresa_id' => $empresa->id, 'nome' => 'Antigo']);

        $resposta = $this->putJson("/api/v1/pesquisa-psicossocial/padroes-formulario/{$padrao->id}", [
            'nome'  => 'Novo Nome',
            'ativo' => false,
        ], $this->headersParaUsuario($usuario));

        $resposta->assertStatus(200)
            ->assertJsonPath('dados.nome', 'Novo Nome')
            ->assertJsonPath('dados.ativo', false);
    }

    public function test_empresa_nao_pode_visualizar_ou_excluir_padrao_de_outra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $usuarioB = $this->criarUsuarioComPermissoes($empresaB, $this->todasPermissoesDoModulo());
        $padraoA = PadraoFormulario::factory()->create(['empresa_id' => $empresaA->id]);

        $this->getJson("/api/v1/pesquisa-psicossocial/padroes-formulario/{$padraoA->id}", $this->headersParaUsuario($usuarioB))
            ->assertStatus(404);
        $this->deleteJson("/api/v1/pesquisa-psicossocial/padroes-formulario/{$padraoA->id}", [], $this->headersParaUsuario($usuarioB))
            ->assertStatus(404);
    }
}
