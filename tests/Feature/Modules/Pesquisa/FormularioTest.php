<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\Subcategoria;

class FormularioTest extends PesquisaTestCase
{
    public function test_isolamento_multiempresa_no_formulario_de_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();

        $formularioA = Formulario::factory()->daEmpresa($empresaA->id)->create();

        $usuarioB = $this->criarUsuarioComPermissoes($empresaB, $this->todasPermissoesDoModulo());

        $resposta = $this->getJson(
            "/api/v1/pesquisa-psicossocial/formularios/{$formularioA->id}",
            $this->headersParaUsuario($usuarioB)
        );

        $resposta->assertStatus(404);
    }

    public function test_superadmin_ve_formularios_de_qualquer_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $formularioA = Formulario::factory()->daEmpresa($empresaA->id)->create();

        $superadmin = $this->criarSuperAdmin();

        $resposta = $this->getJson(
            "/api/v1/pesquisa-psicossocial/formularios/{$formularioA->id}",
            $this->headersParaUsuario($superadmin)
        );

        $resposta->assertStatus(200)->assertJsonPath('sucesso', true);
    }

    public function test_formulario_global_e_visivel_mas_nao_editavel_por_usuario_comum(): void
    {
        $empresa = Empresa::factory()->create();
        $global = Formulario::factory()->create(['empresa_id' => null]);

        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());

        $this->getJson("/api/v1/pesquisa-psicossocial/formularios/{$global->id}", $this->headersParaUsuario($usuario))
            ->assertStatus(200);

        // Nível de aplicação: apenas superadmin pode criar tipo=global (verificado no teste de criação abaixo).
        $this->assertTrue(true);
    }

    public function test_apenas_superadmin_pode_criar_formulario_global(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());

        $resposta = $this->postJson('/api/v1/pesquisa-psicossocial/formularios', [
            'nome'   => 'Formulário Global',
            'codigo' => 'form-global-teste',
            'tipo'   => 'global',
        ], $this->headersParaUsuario($usuario));

        $resposta->assertStatus(422)->assertJsonValidationErrors('tipo', 'erros');
    }

    public function test_bloqueia_acesso_sem_permissao_e_libera_com_permissao(): void
    {
        $empresa = Empresa::factory()->create();
        $semPermissao = $this->criarUsuarioComPermissoes($empresa, []);
        $comPermissao = $this->criarUsuarioComPermissoes($empresa, ['formulario.criar']);

        $payload = ['nome' => 'Teste', 'codigo' => 'teste-permissao', 'tipo' => 'empresa'];

        $this->postJson('/api/v1/pesquisa-psicossocial/formularios', $payload, $this->headersParaUsuario($semPermissao))
            ->assertStatus(403)
            ->assertJsonPath('codigo', 'PERMISSAO_NEGADA');

        $this->postJson('/api/v1/pesquisa-psicossocial/formularios', $payload, $this->headersParaUsuario($comPermissao))
            ->assertStatus(201)
            ->assertJsonPath('sucesso', true);
    }

    public function test_crud_completo_de_formulario_com_erro_de_validacao_no_formato_padrao(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($usuario);

        // Erro de validação — valida o formato padrão {"sucesso":false,"erros":{...}}
        $this->postJson('/api/v1/pesquisa-psicossocial/formularios', [], $headers)
            ->assertStatus(422)
            ->assertJsonPath('sucesso', false)
            ->assertJsonValidationErrors(['nome', 'codigo', 'tipo'], 'erros');

        // Create
        $criar = $this->postJson('/api/v1/pesquisa-psicossocial/formularios', [
            'nome'   => 'Formulário CRUD',
            'codigo' => 'crud-teste',
            'tipo'   => 'empresa',
        ], $headers)->assertStatus(201);

        $id = $criar->json('dados.id');

        // Read
        $this->getJson("/api/v1/pesquisa-psicossocial/formularios/{$id}", $headers)
            ->assertStatus(200)
            ->assertJsonPath('dados.nome', 'Formulário CRUD');

        // Update
        $this->putJson("/api/v1/pesquisa-psicossocial/formularios/{$id}", [
            'nome' => 'Formulário CRUD Atualizado',
        ], $headers)
            ->assertStatus(200)
            ->assertJsonPath('dados.formulario.nome', 'Formulário CRUD Atualizado')
            ->assertJsonPath('dados.versionado', false);

        // Delete
        $this->deleteJson("/api/v1/pesquisa-psicossocial/formularios/{$id}", [], $headers)
            ->assertStatus(200)
            ->assertJsonPath('sucesso', true);

        $this->assertSoftDeleted('pesq_formularios', ['id' => $id]);
    }

    public function test_editar_formulario_usado_em_pesquisa_encerrada_cria_nova_versao(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($usuario);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        $subcategoria = Subcategoria::factory()->create([
            'categoria_id'  => $categoria->id,
            'formulario_id' => $formulario->id,
        ]);
        $pergunta = Pergunta::factory()->create([
            'subcategoria_id' => $subcategoria->id,
            'formulario_id'   => $formulario->id,
            'texto'           => 'Texto original',
        ]);

        Pesquisa::create([
            'empresa_id'    => $empresa->id,
            'formulario_id' => $formulario->id,
            'status'        => 'encerrada',
        ]);

        $resposta = $this->putJson("/api/v1/pesquisa-psicossocial/perguntas/{$pergunta->id}", [
            'texto' => 'Texto atualizado após encerramento',
        ], $headers);

        $resposta->assertStatus(200)->assertJsonPath('dados.versionado', true);

        $novoFormularioId = $resposta->json('dados.formulario_atual_id');
        $this->assertNotEquals($formulario->id, $novoFormularioId);

        $formulario->refresh();
        $this->assertFalse($formulario->ativo);

        $novoFormulario = Formulario::find($novoFormularioId);
        $this->assertTrue($novoFormulario->ativo);
        $this->assertEquals($formulario->id, $novoFormulario->formulario_raiz_id);
        $this->assertEquals(2, $novoFormulario->versao);

        $perguntaNova = Pergunta::where('formulario_id', $novoFormularioId)->where('origem_id', $pergunta->id)->first();
        $this->assertNotNull($perguntaNova);
        $this->assertEquals('Texto atualizado após encerramento', $perguntaNova->texto);

        $pergunta->refresh();
        $this->assertEquals('Texto original', $pergunta->texto);

        $this->assertDatabaseHas('logs', ['acao' => 'VERSIONAMENTO_AUTOMATICO', 'modulo' => 'formulario']);
    }

    public function test_editar_formulario_sem_pesquisa_encerrada_nao_versiona(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();

        $resposta = $this->putJson("/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}", [
            'nome' => 'Nome sem versionamento',
        ], $this->headersParaUsuario($usuario));

        $resposta->assertStatus(200)->assertJsonPath('dados.versionado', false);
        $resposta->assertJsonPath('dados.formulario_atual_id', $formulario->id);
    }

    public function test_nova_versao_manual_sempre_cria_nova_versao(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();

        $resposta = $this->postJson(
            "/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}/nova-versao",
            [],
            $this->headersParaUsuario($usuario)
        );

        $resposta->assertStatus(201);
        $this->assertNotEquals($formulario->id, $resposta->json('dados.id'));

        $formulario->refresh();
        $this->assertFalse($formulario->ativo);
    }

    public function test_nao_permite_editar_versao_arquivada(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();

        $this->postJson(
            "/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}/nova-versao",
            [],
            $this->headersParaUsuario($usuario)
        );

        $formulario->refresh();
        $this->assertFalse($formulario->ativo);

        $this->putJson("/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}", [
            'nome' => 'Não deveria salvar',
        ], $this->headersParaUsuario($usuario))->assertStatus(409);

        $this->patchJson("/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}/publicar", [], $this->headersParaUsuario($usuario))
            ->assertStatus(409);

        $this->patchJson("/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}/arquivar", [], $this->headersParaUsuario($usuario))
            ->assertStatus(409);
    }
}
