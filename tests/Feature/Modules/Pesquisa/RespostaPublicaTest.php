<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Colaborador;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\ConceitoItem;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use App\Modules\Pesquisa\Models\Subcategoria;

class RespostaPublicaTest extends PesquisaTestCase
{
    private function criarCampanhaAtivaComConvites(array $overridesPesquisa = []): array
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $colaborador = Colaborador::factory()->create(['empresa_id' => $empresa->id, 'ativo' => true]);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        $subcategoria = Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);

        $conceito = Conceito::factory()->create(['empresa_id' => null]);
        $item1 = ConceitoItem::factory()->create(['conceito_id' => $conceito->id, 'descricao' => 'Sempre', 'valor' => 5, 'ordem' => 1]);
        ConceitoItem::factory()->create(['conceito_id' => $conceito->id, 'descricao' => 'Nunca', 'valor' => 1, 'ordem' => 2]);

        $perguntaEscala = Pergunta::factory()->create([
            'subcategoria_id' => $subcategoria->id,
            'formulario_id'   => $formulario->id,
            'tipo_pergunta'   => 'escala',
            'conceito_id'     => $conceito->id,
            'obrigatoria'     => true,
        ]);
        $perguntaTexto = Pergunta::factory()->create([
            'subcategoria_id' => $subcategoria->id,
            'formulario_id'   => $formulario->id,
            'tipo_pergunta'   => 'texto',
            'obrigatoria'     => false,
        ]);

        $headers = $this->headersParaUsuario($admin);

        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $headers)->json('dados.id');

        $this->putJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}", array_merge([
            'nome' => 'Campanha de Teste',
        ], $overridesPesquisa), $headers);

        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", [
            'tipo' => 'colaboradores',
            'ids'  => [$colaborador->id],
        ], $headers);

        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);

        $convite = PesquisaConvite::where('pesquisa_id', $idPesquisa)->where('colaborador_id', $colaborador->id)->first();

        return compact('empresa', 'admin', 'colaborador', 'formulario', 'perguntaEscala', 'perguntaTexto', 'item1', 'idPesquisa', 'convite', 'headers');
    }

    public function test_publicar_campanha_gera_um_convite_por_usuario_do_publico_alvo(): void
    {
        $ctx = $this->criarCampanhaAtivaComConvites();

        $this->assertNotNull($ctx['convite']);
        $this->assertEquals($ctx['colaborador']->id, $ctx['convite']->colaborador_id);
        $this->assertNull($ctx['convite']->respondido_em);
    }

    public function test_token_invalido_retorna_404(): void
    {
        $this->getJson('/api/v1/pesquisa-psicossocial/publico/token-que-nao-existe')->assertStatus(404);
    }

    public function test_token_fora_do_periodo_e_bloqueado(): void
    {
        $ctx = $this->criarCampanhaAtivaComConvites([
            'data_inicio' => now()->addDays(5)->toDateString(),
            'data_fim'    => now()->addDays(10)->toDateString(),
        ]);

        $this->getJson('/api/v1/pesquisa-psicossocial/publico/' . $ctx['convite']->token)
            ->assertStatus(409);
    }

    public function test_submete_resposta_valida_e_bloqueia_reenvio(): void
    {
        $ctx = $this->criarCampanhaAtivaComConvites();
        $token = $ctx['convite']->token;

        $resposta = $this->postJson("/api/v1/pesquisa-psicossocial/publico/{$token}/respostas", [
            'respostas' => [
                $ctx['perguntaEscala']->id => $ctx['item1']->id,
                $ctx['perguntaTexto']->id  => 'Tudo bem por aqui.',
            ],
        ]);

        $resposta->assertStatus(201);

        $ctx['convite']->refresh();
        $this->assertNotNull($ctx['convite']->respondido_em);

        $pesquisaResposta = PesquisaResposta::where('pesquisa_id', $ctx['idPesquisa'])->first();
        $this->assertNotNull($pesquisaResposta);
        $this->assertEquals(2, $pesquisaResposta->itens()->count());

        // Reenvio com o mesmo token deve ser bloqueado
        $this->getJson("/api/v1/pesquisa-psicossocial/publico/{$token}")->assertStatus(409);
        $this->postJson("/api/v1/pesquisa-psicossocial/publico/{$token}/respostas", [
            'respostas' => [$ctx['perguntaEscala']->id => $ctx['item1']->id],
        ])->assertStatus(409);
    }

    public function test_resposta_nao_tem_nenhuma_referencia_a_usuario_ou_convite(): void
    {
        $ctx = $this->criarCampanhaAtivaComConvites();

        $this->postJson("/api/v1/pesquisa-psicossocial/publico/{$ctx['convite']->token}/respostas", [
            'respostas' => [$ctx['perguntaEscala']->id => $ctx['item1']->id],
        ])->assertStatus(201);

        $pesquisaResposta = PesquisaResposta::where('pesquisa_id', $ctx['idPesquisa'])->first();
        $colunas = array_keys($pesquisaResposta->getAttributes());

        $this->assertNotContains('user_id', $colunas);
        $this->assertNotContains('convite_id', $colunas);
    }

    public function test_pergunta_obrigatoria_nao_respondida_retorna_422(): void
    {
        $ctx = $this->criarCampanhaAtivaComConvites();

        $this->postJson("/api/v1/pesquisa-psicossocial/publico/{$ctx['convite']->token}/respostas", [
            'respostas' => [$ctx['perguntaTexto']->id => 'só a opcional'],
        ])->assertStatus(422);
    }

    public function test_admin_lista_convites_com_status_de_resposta(): void
    {
        $ctx = $this->criarCampanhaAtivaComConvites();

        $this->postJson("/api/v1/pesquisa-psicossocial/publico/{$ctx['convite']->token}/respostas", [
            'respostas' => [$ctx['perguntaEscala']->id => $ctx['item1']->id],
        ]);

        $resposta = $this->getJson("/api/v1/pesquisa-psicossocial/pesquisas/{$ctx['idPesquisa']}/convites", $ctx['headers']);

        $resposta->assertStatus(200)
            ->assertJsonPath('dados.0.respondido', true)
            ->assertJsonPath('dados.0.email', $ctx['colaborador']->email);
    }
}
