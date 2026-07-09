<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Enums\TipoPergunta;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Subcategoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pergunta>
 */
class PerguntaFactory extends Factory
{
    protected $model = Pergunta::class;

    public function definition(): array
    {
        return [
            'subcategoria_id'    => Subcategoria::factory(),
            'formulario_id'      => fn (array $attributes) => Subcategoria::find($attributes['subcategoria_id'])->formulario_id,
            'conceito_id'        => null,
            'tipo_pergunta'      => TipoPergunta::TEXTO,
            'texto'              => fake()->sentence().'?',
            'descricao'          => null,
            'obrigatoria'        => true,
            'permite_observacao' => false,
            'permite_anexo'      => false,
            'ordem'              => 1,
            'ativo'              => true,
        ];
    }
}
