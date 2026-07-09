<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Formulario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'formulario_id' => Formulario::factory(),
            'nome'          => fake()->words(2, true),
            'descricao'     => fake()->sentence(),
            'ordem'         => 1,
            'ativo'         => true,
        ];
    }
}
