<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Models\Filial;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Models\Formulario;

class PesquisaTest extends PesquisaTestCase
{
    private function criarFormularioPublicado(Empresa $empresa): Formulario
    {
        return Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
    }

    public function test_nao_permite_criar_campanha_a_partir_de_formulario_em_rascunho(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::RASCUNHO]);

        $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $this->headersParaUsuario($usuario))->assertStatus(422);
    }

    public function test_cria_campanha_a_partir_de_formulario_publicado(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = $this->criarFormularioPublicado($empresa);

        $resposta = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $this->headersParaUsuario($usuario));

        $resposta->assertStatus(201)->assertJsonPath('dados.status', 'rascunho');
    }

    public function test_editar_bloqueado_fora_de_rascunho(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = $this->criarFormularioPublicado($empresa);
        $headers = $this->headersParaUsuario($usuario);

        $id = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $headers)->json('dados.id');

        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$id}/publicar", [], $headers)->assertStatus(200);

        $this->putJson("/api/v1/pesquisa-psicossocial/pesquisas/{$id}", ['nome' => 'Não deveria salvar'], $headers)
            ->assertStatus(409);
    }

    public function test_define_publico_alvo_por_filiais(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = $this->criarFormularioPublicado($empresa);
        $filial = Filial::create(['empresa_id' => $empresa->id, 'nome' => 'Filial Centro', 'status' => 'ativo']);
        $headers = $this->headersParaUsuario($usuario);

        $id = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $headers)->json('dados.id');

        $resposta = $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$id}/publico", [
            'tipo' => 'filiais',
            'ids'  => [$filial->id],
        ], $headers);

        $resposta->assertStatus(200)
            ->assertJsonPath('dados.publico.tipo', 'filiais')
            ->assertJsonPath('dados.publico.filial_ids.0', $filial->id);
    }

    public function test_publicar_e_encerrar_campanha(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = $this->criarFormularioPublicado($empresa);
        $headers = $this->headersParaUsuario($usuario);

        $id = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $headers)->json('dados.id');

        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$id}/publicar", [], $headers)
            ->assertStatus(200)->assertJsonPath('dados.status', 'ativa');

        // Não pode publicar de novo (já não está em rascunho)
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$id}/publicar", [], $headers)
            ->assertStatus(409);

        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$id}/encerrar", [], $headers)
            ->assertStatus(200)->assertJsonPath('dados.status', 'encerrada');
    }

    public function test_encerrar_campanha_dispara_versionamento_do_formulario(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = $this->criarFormularioPublicado($empresa);
        $headers = $this->headersParaUsuario($usuario);

        $id = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $headers)->json('dados.id');

        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$id}/publicar", [], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$id}/encerrar", [], $headers);

        $resposta = $this->putJson("/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}", [
            'nome' => 'Formulário editado após encerramento',
        ], $headers);

        $resposta->assertStatus(200)->assertJsonPath('dados.versionado', true);
    }
}
