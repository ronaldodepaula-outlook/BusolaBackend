<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Subcategoria;

class SubcategoriaTest extends PesquisaTestCase
{
    public function test_reordenar_subcategorias(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);

        $s1 = Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id, 'ordem' => 1]);
        $s2 = Subcategoria::factory()->create(['categoria_id' => $categoria->id, 'formulario_id' => $formulario->id, 'ordem' => 2]);

        $resposta = $this->patchJson(
            "/api/v1/pesquisa-psicossocial/categorias/{$categoria->id}/subcategorias/reordenar",
            ['ids' => [$s2->id, $s1->id]],
            $this->headersParaUsuario($usuario)
        );

        $resposta->assertStatus(200)->assertJsonPath('dados.versionado', false);

        $this->assertEquals(1, $s2->fresh()->ordem);
        $this->assertEquals(2, $s1->fresh()->ordem);
    }

    public function test_reordenar_rejeita_subcategoria_de_outra_categoria(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);
        $outraCategoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);

        $subDeOutra = Subcategoria::factory()->create(['categoria_id' => $outraCategoria->id, 'formulario_id' => $formulario->id]);

        $resposta = $this->patchJson(
            "/api/v1/pesquisa-psicossocial/categorias/{$categoria->id}/subcategorias/reordenar",
            ['ids' => [$subDeOutra->id]],
            $this->headersParaUsuario($usuario)
        );

        $resposta->assertStatus(422);
    }
}
