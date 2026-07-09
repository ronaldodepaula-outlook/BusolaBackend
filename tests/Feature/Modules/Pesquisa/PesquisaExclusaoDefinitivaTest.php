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
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Models\Subcategoria;
use Illuminate\Support\Facades\Storage;

class PesquisaExclusaoDefinitivaTest extends PesquisaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * Campanha ativa, com convite, resposta coletada, plano de ação e
     * relatório técnico gerados — o cenário completo que a exclusão
     * definitiva precisa varrer.
     */
    private function criarCampanhaComConteudoCompleto(): array
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

        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", [
            'tipo' => 'colaboradores',
            'ids'  => [$colaborador->id],
        ], $headers);

        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);

        $convite = PesquisaConvite::where('pesquisa_id', $idPesquisa)->firstOrFail();

        $this->postJson("/api/v1/pesquisa-psicossocial/publico/{$convite->token}/respostas", [
            'respostas' => [$pergunta->id => $item1->id],
        ])->assertStatus(201);

        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/plano-acao/gerar", [], $headers);

        $caminhoRelatorio = $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/relatorios-tecnicos", [], $headers)
            ->assertStatus(201)
            ->json('dados.arquivo_path');

        return compact('empresa', 'admin', 'colaborador', 'idPesquisa', 'caminhoRelatorio', 'headers');
    }

    public function test_admin_comum_nao_pode_excluir_campanha_definitivamente(): void
    {
        $ctx = $this->criarCampanhaComConteudoCompleto();

        $this->deleteJson("/api/v1/pesquisa-psicossocial/pesquisas/{$ctx['idPesquisa']}/definitivo", [], $ctx['headers'])
            ->assertStatus(403);

        $this->assertNotNull(Pesquisa::find($ctx['idPesquisa']));
    }

    public function test_superadmin_exclui_campanha_ativa_definitivamente_com_cascata_completa(): void
    {
        $ctx = $this->criarCampanhaComConteudoCompleto();
        $superadmin = $this->criarSuperAdmin();

        Storage::disk('local')->assertExists($ctx['caminhoRelatorio']);

        $this->deleteJson("/api/v1/pesquisa-psicossocial/pesquisas/{$ctx['idPesquisa']}/definitivo", [], $this->headersParaUsuario($superadmin))
            ->assertStatus(200);

        $this->assertNull(Pesquisa::withTrashed()->find($ctx['idPesquisa']));
        $this->assertDatabaseCount('pesq_pesquisa_publico', 0);
        $this->assertDatabaseCount('pesq_pesquisa_convites', 0);
        $this->assertDatabaseCount('pesq_pesquisa_respostas', 0);
        $this->assertDatabaseCount('pesq_pesquisa_respostas_itens', 0);
        $this->assertDatabaseCount('pesq_planos_acao', 0);
        $this->assertDatabaseCount('pesq_relatorios_tecnicos', 0);

        Storage::disk('local')->assertMissing($ctx['caminhoRelatorio']);

        // O colaborador (cadastro da empresa) não é afetado — só a campanha e seu conteúdo coletado.
        $this->assertNotNull(Colaborador::find($ctx['colaborador']->id));
    }

    public function test_superadmin_recebe_404_ao_excluir_campanha_inexistente(): void
    {
        $superadmin = $this->criarSuperAdmin();

        $this->deleteJson('/api/v1/pesquisa-psicossocial/pesquisas/999999/definitivo', [], $this->headersParaUsuario($superadmin))
            ->assertStatus(404);
    }
}
