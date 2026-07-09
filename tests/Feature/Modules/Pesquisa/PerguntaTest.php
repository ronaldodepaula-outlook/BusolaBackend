<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Subcategoria;

class PerguntaTest extends PesquisaTestCase
{
    private function criarSubcategoria(Empresa $empresa): Subcategoria
    {
        $formulario = Formulario::factory()->daEmpresa($empresa->id)->create();
        $categoria = Categoria::factory()->create(['formulario_id' => $formulario->id]);

        return Subcategoria::factory()->create([
            'categoria_id'  => $categoria->id,
            'formulario_id' => $formulario->id,
        ]);
    }

    public function test_pergunta_do_tipo_escala_exige_conceito(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $subcategoria = $this->criarSubcategoria($empresa);

        $this->postJson("/api/v1/pesquisa-psicossocial/subcategorias/{$subcategoria->id}/perguntas", [
            'tipo_pergunta' => 'escala',
            'texto'         => 'Como você avalia sua carga de trabalho?',
        ], $this->headersParaUsuario($usuario))
            ->assertStatus(422)
            ->assertJsonValidationErrors('conceito_id', 'erros');
    }

    public function test_pergunta_do_tipo_texto_nao_exige_conceito(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $subcategoria = $this->criarSubcategoria($empresa);

        $this->postJson("/api/v1/pesquisa-psicossocial/subcategorias/{$subcategoria->id}/perguntas", [
            'tipo_pergunta' => 'texto',
            'texto'         => 'Descreva sua rotina de trabalho.',
        ], $this->headersParaUsuario($usuario))
            ->assertStatus(201);
    }

    public function test_criar_pergunta_com_conceito_valido(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $subcategoria = $this->criarSubcategoria($empresa);
        $conceito = Conceito::factory()->create(['empresa_id' => null]);

        $resposta = $this->postJson("/api/v1/pesquisa-psicossocial/subcategorias/{$subcategoria->id}/perguntas", [
            'tipo_pergunta' => 'unica_escolha',
            'texto'         => 'Com que frequência você se sente sobrecarregado?',
            'conceito_id'   => $conceito->id,
        ], $this->headersParaUsuario($usuario));

        $resposta->assertStatus(201)->assertJsonPath('dados.pergunta.conceito_id', $conceito->id);
    }
}
