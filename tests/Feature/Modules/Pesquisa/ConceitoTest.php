<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;

class ConceitoTest extends PesquisaTestCase
{
    public function test_crud_completo_de_conceito_e_itens(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($usuario);

        $criar = $this->postJson('/api/v1/pesquisa-psicossocial/conceitos', [
            'nome' => 'Escala de Satisfação',
            'tipo' => 'escala_likert',
        ], $headers)->assertStatus(201);

        $conceitoId = $criar->json('dados.id');

        $this->postJson("/api/v1/pesquisa-psicossocial/conceitos/{$conceitoId}/itens", [
            'descricao' => 'Muito satisfeito',
            'valor'     => 5,
        ], $headers)->assertStatus(201);

        $this->getJson("/api/v1/pesquisa-psicossocial/conceitos/{$conceitoId}", $headers)
            ->assertStatus(200)
            ->assertJsonCount(1, 'dados.itens');

        $this->deleteJson("/api/v1/pesquisa-psicossocial/conceitos/{$conceitoId}", [], $headers)
            ->assertStatus(200);

        $this->assertSoftDeleted('pesq_conceitos', ['id' => $conceitoId]);
    }

    public function test_usuario_comum_sem_empresa_id_cria_conceito_na_propria_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());

        $resposta = $this->postJson('/api/v1/pesquisa-psicossocial/conceitos', [
            'nome' => 'Escala da Empresa',
            'tipo' => 'escala_likert',
        ], $this->headersParaUsuario($usuario));

        $resposta->assertStatus(201)->assertJsonPath('dados.empresa_id', $empresa->id);
    }

    public function test_usuario_comum_nao_pode_criar_conceito_para_outra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresaA, $this->todasPermissoesDoModulo());

        $this->postJson('/api/v1/pesquisa-psicossocial/conceitos', [
            'nome'       => 'Escala de Outra Empresa',
            'tipo'       => 'escala_likert',
            'empresa_id' => $empresaB->id,
        ], $this->headersParaUsuario($usuario))
            ->assertStatus(422)
            ->assertJsonValidationErrors('empresa_id', 'erros');
    }
}
