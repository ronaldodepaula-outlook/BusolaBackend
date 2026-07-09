<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\ConceitoItem;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaAcessoPublico;
use App\Modules\Pesquisa\Models\Subcategoria;

class RespostaPublicaGlobalTest extends PesquisaTestCase
{
    private function criarCampanhaAtivaComLinkGlobal(array $overrides = []): array
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        $subcategoria = Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);

        $conceito = Conceito::factory()->create(['empresa_id' => null]);
        $item = ConceitoItem::factory()->create(['conceito_id' => $conceito->id, 'valor' => 5]);

        $pergunta = Pergunta::factory()->create([
            'subcategoria_id' => $subcategoria->id,
            'formulario_id'   => $formulario->id,
            'tipo_pergunta'   => 'escala',
            'conceito_id'     => $conceito->id,
            'obrigatoria'     => true,
        ]);

        $headers = $this->headersParaUsuario($admin);

        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $headers)->json('dados.id');

        $this->putJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}", $overrides, $headers);
        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", ['tipo' => 'toda_empresa'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);

        $pesquisa = Pesquisa::find($idPesquisa);

        return compact('empresa', 'admin', 'formulario', 'pergunta', 'item', 'idPesquisa', 'pesquisa', 'headers');
    }

    public function test_publicar_gera_um_link_global_para_a_campanha(): void
    {
        $ctx = $this->criarCampanhaAtivaComLinkGlobal();

        $this->assertNotNull($ctx['pesquisa']->link_publico_token);
    }

    public function test_link_global_invalido_retorna_404(): void
    {
        $this->getJson('/api/v1/pesquisa-psicossocial/publico/global/nao-existe?sessao_token=abc')
            ->assertStatus(404);
    }

    public function test_duas_sessoes_diferentes_podem_responder_pelo_mesmo_link_global(): void
    {
        $ctx = $this->criarCampanhaAtivaComLinkGlobal();
        $token = $ctx['pesquisa']->link_publico_token;

        foreach (['sessao-dispositivo-1', 'sessao-dispositivo-2'] as $sessao) {
            $this->postJson("/api/v1/pesquisa-psicossocial/publico/global/{$token}/respostas", [
                'sessao_token' => $sessao,
                'respostas'    => [$ctx['pergunta']->id => $ctx['item']->id],
            ])->assertStatus(201);
        }

        $this->assertEquals(2, PesquisaAcessoPublico::where('pesquisa_id', $ctx['idPesquisa'])->whereNotNull('respondido_em')->count());
    }

    public function test_mesma_sessao_nao_pode_responder_duas_vezes(): void
    {
        $ctx = $this->criarCampanhaAtivaComLinkGlobal();
        $token = $ctx['pesquisa']->link_publico_token;

        $this->postJson("/api/v1/pesquisa-psicossocial/publico/global/{$token}/respostas", [
            'sessao_token' => 'mesma-sessao',
            'respostas'    => [$ctx['pergunta']->id => $ctx['item']->id],
        ])->assertStatus(201);

        $this->getJson("/api/v1/pesquisa-psicossocial/publico/global/{$token}?sessao_token=mesma-sessao")
            ->assertStatus(409);

        $this->postJson("/api/v1/pesquisa-psicossocial/publico/global/{$token}/respostas", [
            'sessao_token' => 'mesma-sessao',
            'respostas'    => [$ctx['pergunta']->id => $ctx['item']->id],
        ])->assertStatus(409);
    }

    public function test_link_global_fora_do_periodo_e_bloqueado(): void
    {
        $ctx = $this->criarCampanhaAtivaComLinkGlobal([
            'data_inicio' => now()->addDays(5)->toDateString(),
            'data_fim'    => now()->addDays(10)->toDateString(),
        ]);

        $this->getJson("/api/v1/pesquisa-psicossocial/publico/global/{$ctx['pesquisa']->link_publico_token}?sessao_token=x")
            ->assertStatus(409);
    }

    public function test_resposta_via_link_global_nao_tem_referencia_ao_acesso(): void
    {
        $ctx = $this->criarCampanhaAtivaComLinkGlobal();

        $this->postJson("/api/v1/pesquisa-psicossocial/publico/global/{$ctx['pesquisa']->link_publico_token}/respostas", [
            'sessao_token' => 'sessao-x',
            'respostas'    => [$ctx['pergunta']->id => $ctx['item']->id],
        ])->assertStatus(201);

        $resposta = \App\Modules\Pesquisa\Models\PesquisaResposta::where('pesquisa_id', $ctx['idPesquisa'])->first();
        $colunas = array_keys($resposta->getAttributes());

        $this->assertNotContains('acesso_publico_id', $colunas);
        $this->assertNotContains('sessao_token', $colunas);
        $this->assertNotContains('ip', $colunas);
    }
}
