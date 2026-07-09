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

/**
 * Valida o ciclo PDCA (Planejar → Executar → Verificar → Agir) de uma ação
 * do plano de ação: transições sequenciais obrigatórias, exigência de
 * evidência/parecer em cada avanço, e reabertura automática do ciclo quando
 * a ação não é considerada eficaz.
 */
class PdcaPlanoAcaoTest extends PesquisaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanoAcaoTemplateSeeder::class);
    }

    private function criarAcaoDeExemplo(): array
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($admin);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create([
            'formulario_id'        => $formulario->id,
            'categoria_referencia' => 'Jornada de Trabalho',
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

        $ghe = Ghe::create(['empresa_id' => $empresa->id, 'nome' => 'GHE Único']);
        for ($i = 0; $i < 5; $i++) {
            $resposta = PesquisaResposta::create(['pesquisa_id' => $idPesquisa, 'ghe_id' => $ghe->id, 'iniciado_em' => now(), 'finalizado_em' => now(), 'status' => 'concluida']);
            PesquisaRespostaItem::create(['pesquisa_resposta_id' => $resposta->id, 'pergunta_id' => $pergunta->id, 'conceito_item_id' => $item5->id]);
        }

        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/plano-acao/gerar", [], $headers);
        $idAcao = $this->getJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/plano-acao", $headers)->json('dados.0.id');

        return [$idAcao, $headers];
    }

    public function test_acao_recem_gerada_comeca_na_fase_planejar(): void
    {
        [$idAcao, $headers] = $this->criarAcaoDeExemplo();

        $acao = $this->buscarAcao($idAcao, $headers);
        $this->assertSame('planejar', $acao['fase_pdca']['value']);
        $this->assertSame(1, $acao['ciclo_pdca']);
        $this->assertSame('executar', $acao['fase_pdca']['proxima']);
    }

    public function test_nao_permite_pular_etapas_do_pdca(): void
    {
        [$idAcao, $headers] = $this->criarAcaoDeExemplo();

        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", [
            'fase' => 'verificar', 'evidencia_execucao' => 'ok',
        ], $headers)->assertStatus(409);
    }

    public function test_exige_evidencia_para_avancar_de_executar_para_verificar(): void
    {
        [$idAcao, $headers] = $this->criarAcaoDeExemplo();

        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", ['fase' => 'executar'], $headers)
            ->assertStatus(200);

        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", ['fase' => 'verificar'], $headers)
            ->assertStatus(422);

        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", [
            'fase' => 'verificar', 'evidencia_execucao' => 'Escalas revisadas e publicadas para a equipe.',
        ], $headers)->assertStatus(200)
            ->assertJsonPath('dados.fase_pdca.value', 'verificar')
            ->assertJsonPath('dados.evidencia_execucao', 'Escalas revisadas e publicadas para a equipe.');
    }

    public function test_ciclo_completo_eficaz_encerra_a_acao(): void
    {
        [$idAcao, $headers] = $this->criarAcaoDeExemplo();

        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", ['fase' => 'executar'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", [
            'fase' => 'verificar', 'evidencia_execucao' => 'Evidência de execução.',
        ], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", [
            'fase' => 'agir', 'parecer_verificacao' => 'Validado com a liderança.',
        ], $headers)->assertStatus(200)->assertJsonPath('dados.fase_pdca.value', 'agir');

        $resp = $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/concluir-ciclo", [
            'eficacia' => 'eficaz',
        ], $headers)->assertStatus(200);

        $this->assertSame('agir', $resp->json('dados.fase_pdca.value'));
        $this->assertSame('concluido', $resp->json('dados.status.value'));
        $this->assertSame(1, $resp->json('dados.ciclo_pdca'));
        $this->assertFalse($resp->json('dados.necessita_nova_acao'));
    }

    public function test_ciclo_ineficaz_reabre_automaticamente_um_novo_ciclo(): void
    {
        [$idAcao, $headers] = $this->criarAcaoDeExemplo();

        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", ['fase' => 'executar'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", [
            'fase' => 'verificar', 'evidencia_execucao' => 'Evidência parcial.',
        ], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/avancar-fase", [
            'fase' => 'agir', 'parecer_verificacao' => 'Resultado abaixo do esperado.',
        ], $headers);

        $resp = $this->patchJson("/api/v1/pesquisa-psicossocial/plano-acao/{$idAcao}/concluir-ciclo", [
            'eficacia' => 'ineficaz',
        ], $headers)->assertStatus(200);

        $this->assertSame('planejar', $resp->json('dados.fase_pdca.value'));
        $this->assertSame('pendente', $resp->json('dados.status.value'));
        $this->assertSame(2, $resp->json('dados.ciclo_pdca'));
        $this->assertTrue($resp->json('dados.necessita_nova_acao'));
        $this->assertCount(1, $resp->json('dados.historico_pdca'));
        $this->assertNull($resp->json('dados.evidencia_execucao'));
    }

    private function buscarAcao(int $idAcao, array $headers): array
    {
        // A rota de listagem exige o id da pesquisa; usamos a tabela diretamente via API de detalhe indireta.
        $planoAcao = \App\Modules\Pesquisa\Models\PlanoAcao::findOrFail($idAcao);

        return $this->getJson(
            "/api/v1/pesquisa-psicossocial/pesquisas/{$planoAcao->pesquisa_id}/plano-acao",
            $headers
        )->json('dados.0');
    }
}
