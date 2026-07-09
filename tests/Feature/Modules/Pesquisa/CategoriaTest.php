<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Formulario;

class CategoriaTest extends PesquisaTestCase
{
    public function test_crud_completo_de_categoria(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($usuario);

        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();

        $criar = $this->postJson("/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}/categorias", [
            'nome' => 'Carga de Trabalho',
        ], $headers)->assertStatus(201);

        $id = $criar->json('dados.categoria.id');

        $this->getJson("/api/v1/pesquisa-psicossocial/categorias/{$id}", $headers)
            ->assertStatus(200)
            ->assertJsonPath('dados.nome', 'Carga de Trabalho');

        $this->putJson("/api/v1/pesquisa-psicossocial/categorias/{$id}", ['nome' => 'Carga de Trabalho Atualizada'], $headers)
            ->assertStatus(200)
            ->assertJsonPath('dados.categoria.nome', 'Carga de Trabalho Atualizada');

        $this->deleteJson("/api/v1/pesquisa-psicossocial/categorias/{$id}", [], $headers)
            ->assertStatus(200);

        $this->assertSoftDeleted('pesq_categorias', ['id' => $id]);
    }

    public function test_reordenar_categorias(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();

        $c1 = Categoria::factory()->create(['formulario_id' => $formulario->id, 'ordem' => 1]);
        $c2 = Categoria::factory()->create(['formulario_id' => $formulario->id, 'ordem' => 2]);

        $resposta = $this->patchJson(
            "/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}/categorias/reordenar",
            ['ids' => [$c2->id, $c1->id]],
            $this->headersParaUsuario($usuario)
        );

        $resposta->assertStatus(200)->assertJsonPath('dados.versionado', false);

        $this->assertEquals(1, $c2->fresh()->ordem);
        $this->assertEquals(2, $c1->fresh()->ordem);
    }

    public function test_reordenar_rejeita_id_que_nao_pertence_ao_formulario(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();
        $outroFormulario = Formulario::factory()->daEmpresa($empresa->id)->create();

        $categoriaDeOutroFormulario = Categoria::factory()->create(['formulario_id' => $outroFormulario->id]);

        $resposta = $this->patchJson(
            "/api/v1/pesquisa-psicossocial/formularios/{$formulario->id}/categorias/reordenar",
            ['ids' => [$categoriaDeOutroFormulario->id]],
            $this->headersParaUsuario($usuario)
        );

        $resposta->assertStatus(422);
    }
}
