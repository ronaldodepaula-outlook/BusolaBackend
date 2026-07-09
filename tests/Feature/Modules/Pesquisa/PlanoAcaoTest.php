<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\ConceitoItem;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Ghe;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use App\Modules\Pesquisa\Models\PesquisaRespostaItem;
use App\Modules\Pesquisa\Models\Subcategoria;
use Database\Seeders\PlanoAcaoTemplateSeeder;

class PlanoAcaoTest extends PesquisaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanoAcaoTemplateSeeder::class);
    }

    public function test_gera_plano_de_acao_a_partir_da_classificacao_de_risco_e_e_idempotente(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($admin);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create([
            'formulario_id'        => $formulario->id,
            'categoria_referencia' => 'Jornada de Trabalho', // severidade oficial = 4
        ]);
        $subcategoria = Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);
        $conceito = Conceito::factory()->create(['empresa_id' => null]);
        $item5 = ConceitoItem::factory()->create(['conceito_id' => $conceito->id, 'valor' => 5]);
        $pergunta = Pergunta::factory()->create([
            'subcategoria_id' => $subcategoria->id, 'formulario_id' => $formulario->id,
            'tipo_pergunta' => 'escala', 'conceito_id' => $conceito->id,
        ]);

        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', ['formulario_id' => $formulario->id], $headers)->json('dados.id');
        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", ['tipo' => 'toda_empresa'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);

        // Média 5 -> P5, severidade 4 -> matriz P5xS4 = Intolerável -> nivelBaseAcao = Crítico
        $ghe = Ghe::create(['empresa_id' => $empresa->id, 'nome' => 'GHE Único']);
        for ($i = 0; $i < 5; $i++) {
            $resposta = PesquisaResposta::create(['pesquisa_id' => $idPesquisa, 'ghe_id' => $ghe->id, 'iniciado_em' => now(), 'finalizado_em' => now(), 'status' => 'concluida']);
            PesquisaRespostaItem::create(['pesquisa_resposta_id' => $resposta->id, 'pergunta_id' => $pergunta->id, 'conceito_item_id' => $item5->id]);
        }

        $gerar1 = $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/plano-acao/gerar", [], $headers)->assertStatus(200);
        $this->assertSame(3, $gerar1->json('dados.geradas')); // 1 categoria x 1 grupo x 3 tipos de controle
        $this->assertSame(0, $gerar1->json('dados.atualizadas'));

        $lista = $this->getJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/plano-acao", $headers)->assertStatus(200)->json('dados');
        $this->assertCount(3, $lista);

        $porTipo = collect($lista)->keyBy(fn ($a) => $a['tipo_controle']['value']);
        $estrutural = $porTipo['estrutural'];

        $this->assertStringContainsStringIgnoringCase('não tolerável', $estrutural['nivel_risco']['label']);
        $this->assertStringContainsString('revisar escalas, pausas e dimensionamento', $estrutural['acao']);
        $this->assertSame('6 meses', $estrutural['prazo']);
        $primeira = $lista[0];

        // idempotência: gerar de novo não duplica, apenas atualiza
        $gerar2 = $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/plano-acao/gerar", [], $headers)->assertStatus(200);
        $this->assertSame(0, $gerar2->json('dados.geradas'));
        $this->assertSame(3, $gerar2->json('dados.atualizadas'));

        // atualizar status de uma ação
        $idAcao = $primeira['id'];
        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}", ['status' => 'concluido', 'responsavel' => 'RH'], $headers)
            ->assertStatus(200)
            ->assertJsonPath('dados.status.value', 'concluido')
            ->assertJsonPath('dados.responsavel', 'RH');
    }
}
