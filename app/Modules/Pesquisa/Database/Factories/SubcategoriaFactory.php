<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Subcategoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subcategoria>
 */
class SubcategoriaFactory extends Factory
{
    protected $model = Subcategoria::class;

    public function definition(): array
    {
        return [
            'categoria_id'  => Categoria::factory(),
            'formulario_id' => fn (array $attributes) => Categoria::find($attributes['categoria_id'])->formulario_id,
            'nome'          => fake()->words(2, true),
            'descricao'     => fake()->sentence(),
            'ordem'         => 1,
            'ativo'         => true,
        ];
    }
}
