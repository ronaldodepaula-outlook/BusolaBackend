<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Enums\StatusPesquisa;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pesquisa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pesquisa>
 */
class PesquisaFactory extends Factory
{
    protected $model = Pesquisa::class;

    public function definition(): array
    {
        return [
            'formulario_id' => Formulario::factory(),
            'nome'          => fake()->sentence(3),
            'descricao'     => fake()->sentence(),
            'anonima'       => true,
            'status'        => StatusPesquisa::RASCUNHO,
        ];
    }
}
