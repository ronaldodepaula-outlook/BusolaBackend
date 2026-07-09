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

/**
 * Valida a integração do RiscoCalculator com o ResultadoService: classificação
 * de risco por categoria (fixa por severidade oficial) e agregação por GHE
 * com supressão de grupos abaixo do quantitativo mínimo de respondentes.
 */
class ResultadoRiscoTest extends PesquisaTestCase
{
    public function test_categoria_com_referencia_oficial_e_classificada_por_risco_e_agregada_por_ghe(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($admin);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create([
            'formulario_id'        => $formulario->id,
            'categoria_referencia' => 'Jornada de Trabalho', // severidade oficial = 4
            'severidade'           => null,
        ]);
        $subcategoria = Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);

        $conceito = Conceito::factory()->create(['empresa_id' => null]);
        $itens = [];
        foreach ([1, 2, 3, 4, 5] as $valor) {
            $itens[$valor] = ConceitoItem::factory()->create(['conceito_id' => $conceito->id, 'valor' => $valor]);
        }

        $pergunta = Pergunta::factory()->create([
            'subcategoria_id' => $subcategoria->id, 'formulario_id' => $formulario->id,
            'tipo_pergunta' => 'escala', 'conceito_id' => $conceito->id,
        ]);

        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
            'minimo_respondentes' => 5,
        ], $headers)->json('dados.id');
        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", ['tipo' => 'toda_empresa'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);

        $gheGrande = Ghe::create(['empresa_id' => $empresa->id, 'nome' => 'GHE Grande']);
        $ghePequeno = Ghe::create(['empresa_id' => $empresa->id, 'nome' => 'GHE Pequeno']);

        // GHE grande: 5 respostas com valor 4 (media 4 -> P4, S4 -> Substancial)
        for ($i = 0; $i < 5; $i++) {
            $resposta = PesquisaResposta::create(['pesquisa_id' => $idPesquisa, 'ghe_id' => $gheGrande->id, 'iniciado_em' => now(), 'finalizado_em' => now(), 'status' => 'concluida']);
            PesquisaRespostaItem::create(['pesquisa_resposta_id' => $resposta->id, 'pergunta_id' => $pergunta->id, 'conceito_item_id' => $itens[4]->id]);
        }

        // GHE pequeno: apenas 2 respostas (abaixo do mínimo de 5) -> deve ser agregado
        for ($i = 0; $i < 2; $i++) {
            $resposta = PesquisaResposta::create(['pesquisa_id' => $idPesquisa, 'ghe_id' => $ghePequeno->id, 'iniciado_em' => now(), 'finalizado_em' => now(), 'status' => 'concluida']);
            PesquisaRespostaItem::create(['pesquisa_resposta_id' => $resposta->id, 'pergunta_id' => $pergunta->id, 'conceito_item_id' => $itens[1]->id]);
        }

        $dados = $this->getJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/resultados", $headers)
            ->assertStatus(200)
            ->json('dados');

        $categoriaResultado = collect($dados['categorias'])->firstWhere('id', $categoria->id);

        $this->assertSame(4, $categoriaResultado['severidade']);
        $this->assertSame('substancial', $categoriaResultado['risco']['nivel']);
        $this->assertSame('🟠', $categoriaResultado['risco']['farol']);

        $grupos = collect($categoriaResultado['grupos_ghe'])->keyBy('nome');
        $this->assertArrayHasKey('GHE Grande', $grupos->all());
        $this->assertSame(5, $grupos['GHE Grande']['total_respostas']);
        $this->assertSame('substancial', $grupos['GHE Grande']['risco']['nivel']);

        $this->assertArrayHasKey('Grupo agregado (confidencialidade)', $grupos->all());
        $this->assertSame(2, $grupos['Grupo agregado (confidencialidade)']['total_respostas']);
        $this->assertArrayNotHasKey('GHE Pequeno', $grupos->all());

        $this->assertSame(1, $dados['resumo_risco']['substancial'] ?? null);
    }
}
