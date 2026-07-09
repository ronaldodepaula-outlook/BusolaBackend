<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Enums\TipoConceito;
use App\Modules\Pesquisa\Models\Conceito;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conceito>
 */
class ConceitoFactory extends Factory
{
    protected $model = Conceito::class;

    public function definition(): array
    {
        return [
            'empresa_id' => null,
            'nome'       => fake()->words(2, true),
            'descricao'  => fake()->sentence(),
            'tipo'       => TipoConceito::ESCALA_LIKERT,
            'ativo'      => true,
        ];
    }
}
