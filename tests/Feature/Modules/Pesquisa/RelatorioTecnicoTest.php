<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Colaborador;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Ghe;
use App\Modules\Pesquisa\Models\Setor;
use App\Modules\Pesquisa\Models\Subcategoria;
use Illuminate\Support\Facades\Storage;

class RelatorioTecnicoTest extends PesquisaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_gera_relatorio_tecnico_em_pdf_e_persiste_o_registro(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($admin);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);

        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', ['formulario_id' => $formulario->id], $headers)->json('dados.id');
        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", ['tipo' => 'toda_empresa'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);

        $resp = $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/relatorios-tecnicos", [
            'responsavel_tecnico_nome'     => 'Cinthia Santos Gadelha',
            'responsavel_tecnico_registro' => 'CRP 11/15242',
        ], $headers);

        $resp->assertStatus(201);
        $caminho = $resp->json('dados.arquivo_path');
        Storage::disk('local')->assertExists($caminho);
        $this->assertStringEndsWith('.pdf', $caminho);

        $this->assertDatabaseHas('pesq_relatorios_tecnicos', [
            'pesquisa_id'              => $idPesquisa,
            'empresa_id'               => $empresa->id,
            'responsavel_tecnico_nome' => 'Cinthia Santos Gadelha',
        ]);

        $lista = $this->getJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/relatorios-tecnicos", $headers)
            ->assertStatus(200)
            ->json('dados');
        $this->assertCount(1, $lista);
    }

    public function test_gera_relatorio_com_ghes_e_setores_povoados(): void
    {
        // Regressão: Eloquent\Collection::map() para um array desce para
        // Illuminate\Support\Collection — composicaoGhe() precisa aceitar isso.
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($admin);

        $ghe = Ghe::create(['empresa_id' => $empresa->id, 'nome' => 'GHE 01 – Comercial']);
        $setor = Setor::create(['empresa_id' => $empresa->id, 'ghe_id' => $ghe->id, 'nome' => 'Comercial']);
        Colaborador::factory()->create(['empresa_id' => $empresa->id, 'setor_id' => $setor->id, 'ativo' => true]);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);

        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', ['formulario_id' => $formulario->id], $headers)->json('dados.id');
        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", ['tipo' => 'toda_empresa'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);

        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/relatorios-tecnicos", [], $headers)
            ->assertStatus(201);
    }

    public function test_empresa_nao_pode_baixar_relatorio_de_outra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $adminA = $this->criarUsuarioComPermissoes($empresaA, $this->todasPermissoesDoModulo());
        $adminB = $this->criarUsuarioComPermissoes($empresaB, $this->todasPermissoesDoModulo());

        $formulario = Formulario::factory()->daEmpresa($empresaA->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);

        $headersA = $this->headersParaUsuario($adminA);
        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', ['formulario_id' => $formulario->id], $headersA)->json('dados.id');
        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", ['tipo' => 'toda_empresa'], $headersA);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headersA);

        $idRelatorio = $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/relatorios-tecnicos", [], $headersA)->json('dados.id');

        $this->getJson("/api/v1/pesquisa-psicossocial/relatorios-tecnicos/{$idRelatorio}/download", $this->headersParaUsuario($adminB))
            ->assertStatus(403);
    }

    public function test_superadmin_lista_relatorios_de_todas_as_empresas(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $superadmin = $this->criarSuperAdmin();

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create(['status' => StatusFormulario::PUBLICADO]);
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id]);

        $headers = $this->headersParaUsuario($admin);
        $idPesquisa = $this->postJson('/api/v1/pesquisa-psicossocial/pesquisas', ['formulario_id' => $formulario->id], $headers)->json('dados.id');
        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publico", ['tipo' => 'toda_empresa'], $headers);
        $this->patchJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/publicar", [], $headers);
        $this->postJson("/api/v1/pesquisa-psicossocial/pesquisas/{$idPesquisa}/relatorios-tecnicos", [], $headers);

        $this->getJson('/api/v1/pesquisa-psicossocial/relatorios-tecnicos', $this->headersParaUsuario($superadmin))
            ->assertStatus(200)
            ->assertJsonPath('dados.total', 1);

        // admin comum não pode acessar a listagem cross-empresa
        $this->getJson('/api/v1/pesquisa-psicossocial/relatorios-tecnicos', $headers)->assertStatus(403);
    }
}
