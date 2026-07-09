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
use App\Modules\Pesquisa\Models\PesquisaRespostaItem;
use App\Modules\Pesquisa\Models\Subcategoria;

class ResultadoTest extends PesquisaTestCase
{
    public function test_agrega_taxa_de_resposta_media_por_categoria_e_distribuicao(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($admin);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        $subcategoria = Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);

        $conceito = Conceito::factory()->create(['empresa_id' => null]);
        $itemAlto = ConceitoItem::factory()->create(['conceito_id' => $conceito->id, 'descricao' => 'Sempre', 'valor' => 5]);
        $itemBaixo = ConceitoItem::factory()->create(['conceito_id' => $conceito->id, 'descricao' => 'Nunca', 'valor' => 1]);

        $perguntaEscala = Pergunta::factory()->create([
            'subcategoria_id' => $subcategoria->id, 'formulario_id' => $formulario->id,
            'tipo_pergunta' => 'escala', 'conceito_id' => $conceito->id,
        ]);
        $perguntaSimNao = Pergunta::factory()->create([
            'subcategoria_id' => $subcategoria->id, 'formulario_id' => $formulario->id,
            'tipo_pergunta' => 'sim_nao', 'conceito_id' => null,
        ]);

        Colaborador::factory()->create(['empresa_id' => $empresa->id, 'ativo' => true]);

        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $headers)->json('dados.id');

        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", ['tipo' => 'toda_empresa'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);

        // 2 respostas: uma com valor alto (5) + sim, outra com valor baixo (1) + não
        foreach ([[$itemAlto, 'sim'], [$itemBaixo, 'nao']] as [$item, $simNao]) {
            $resposta = PesquisaResposta::create(['pesquisa_id' => $idPesquisa, 'iniciado_em' => now(), 'finalizado_em' => now(), 'status' => 'concluida']);
            PesquisaRespostaItem::create(['pesquisa_resposta_id' => $resposta->id, 'pergunta_id' => $perguntaEscala->id, 'conceito_item_id' => $item->id]);
            PesquisaRespostaItem::create(['pesquisa_resposta_id' => $resposta->id, 'pergunta_id' => $perguntaSimNao->id, 'valor_texto' => $simNao]);
        }

        // marca 1 dos convites (auto-gerados por "toda_empresa" = todos os colaboradores ativos da empresa) como respondido
        $convite = PesquisaConvite::where('pesquisa_id', $idPesquisa)->first();
        $convite?->update(['respondido_em' => now()]);

        $resposta = $this->getJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/resultados", $headers);
        $resposta->assertStatus(200);

        $dados = $resposta->json('dados');

        $this->assertEquals(1, $dados['taxa_resposta']['total_respondidos']);
        $this->assertEquals(3.0, $dados['categorias'][0]['media']); // média de 5 e 1 = 3

        $perguntas = collect($dados['categorias'][0]['perguntas'])->keyBy('id');
        $this->assertEquals(3.0, $perguntas[$perguntaEscala->id]['media']);
        $this->assertEquals(2, $perguntas[$perguntaEscala->id]['total_respostas']);

        $distribuicaoSimNao = collect($perguntas[$perguntaSimNao->id]['distribuicao'])->keyBy('descricao');
        $this->assertEquals(1, $distribuicaoSimNao['Sim']['quantidade']);
        $this->assertEquals(1, $distribuicaoSimNao['Não']['quantidade']);
    }

    public function test_usuario_sem_permissao_resultado_consultar_e_bloqueado(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, ['pesquisa.listar', 'pesquisa.visualizar', 'pesquisa.criar']);
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);

        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', [
            'formulario_id' => $formulario->id,
        ], $this->headersParaUsuario($usuario))->json('dados.id');

        $this->getJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/resultados", $this->headersParaUsuario($usuario))
            ->assertStatus(403);
    }
}
