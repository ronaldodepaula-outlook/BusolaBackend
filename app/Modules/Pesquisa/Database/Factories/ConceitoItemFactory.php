<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\ConceitoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConceitoItem>
 */
class ConceitoItemFactory extends Factory
{
    protected $model = ConceitoItem::class;

    public function definition(): array
    {
        return [
            'conceito_id' => Conceito::factory(),
            'descricao'   => fake()->words(2, true),
            'valor'       => fake()->numberBetween(1, 5),
            'cor'         => null,
            'ordem'       => 1,
        ];
    }
}
