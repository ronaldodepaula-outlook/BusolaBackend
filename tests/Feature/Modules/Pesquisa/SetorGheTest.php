<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Setor;

class SetorGheTest extends PesquisaTestCase
{
    public function test_crud_completo_de_ghe_e_setor(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($admin);

        $idGhe = $this->postJson('/api/v1/pesquisa-psicossocial/ghes', ['nome' => 'GHE 01 – Comercial'], $headers)
            ->assertStatus(201)
            ->json('dados.id');

        $idSetor = $this->postJson('/api/v1/pesquisa-psicossocial/setores', ['nome' => 'Comercial', 'ghe_id' => $idGhe], $headers)
            ->assertStatus(201)
            ->json('dados.id');

        $this->getJson('/api/v1/pesquisa-psicossocial/setores', $headers)
            ->assertStatus(200)
            ->assertJsonPath('dados.0.ghe.nome', 'GHE 01 – Comercial');

        $this->putJson("/api/v1/pesquisa-psicossocial/ghes/{$idGhe}", ['nome' => 'GHE 01 – Comercial e Relacionamento'], $headers)
            ->assertStatus(200);

        $this->deleteJson("/api/v1/pesquisa-psicossocial/setores/{$idSetor}", [], $headers)->assertStatus(200);
        $this->getJson("/api/v1/pesquisa-psicossocial/setores/{$idSetor}", $headers)->assertStatus(404);
    }

    public function test_setores_e_ghes_sao_isolados_por_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $adminA = $this->criarUsuarioComPermissoes($empresaA, $this->todasPermissoesDoModulo());
        $adminB = $this->criarUsuarioComPermissoes($empresaB, $this->todasPermissoesDoModulo());

        $idGheA = $this->postJson('/api/v1/pesquisa-psicossocial/ghes', ['nome' => 'GHE da Empresa A'], $this->headersParaUsuario($adminA))
            ->json('dados.id');

        $this->getJson("/api/v1/pesquisa-psicossocial/ghes/{$idGheA}", $this->headersParaUsuario($adminB))
            ->assertStatus(404);
    }

    public function test_atribuir_setor_a_um_colaborador(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $colaborador = \App\Models\User::factory()->create(['empresa_id' => $empresa->id, 'status' => 'ativo']);
        $setor = Setor::create(['empresa_id' => $empresa->id, 'nome' => 'Comercial']);

        $this->postJson("/api/v1/pesquisa-psicossocial/setores/{$setor->id}/usuario", ['user_id' => $colaborador->id], $this->headersParaUsuario($admin))
            ->assertStatus(200);

        $this->assertDatabaseHas('pesq_usuario_setores', ['user_id' => $colaborador->id, 'setor_id' => $setor->id]);
    }

    public function test_categoria_vinculada_a_referencia_oficial_recebe_severidade_fixa_automaticamente(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO, 'ativo' => true]);

        $resp = $this->postJson("/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}/categorias", [
            'nome'                 => 'Jornada',
            'categoria_referencia' => 'Jornada de Trabalho',
        ], $this->headersParaUsuario($admin));

        $resp->assertStatus(201);
        $this->assertSame(4, $resp->json('dados.categoria.severidade')); // Jornada de Trabalho = S4
    }
}
