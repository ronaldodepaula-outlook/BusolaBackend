<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Enums\StatusResposta;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesquisaResposta>
 */
class PesquisaRespostaFactory extends Factory
{
    protected $model = PesquisaResposta::class;

    public function definition(): array
    {
        return [
            'pesquisa_id'   => Pesquisa::factory(),
            'iniciado_em'   => $this->faker->dateTimeBetween('-1 week', 'now'),
            'finalizado_em' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'status'        => StatusResposta::CONCLUIDA,
        ];
    }
}
