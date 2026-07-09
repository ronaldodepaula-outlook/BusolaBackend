<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaAcessoPublico;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PesquisaAcessoPublico>
 */
class PesquisaAcessoPublicoFactory extends Factory
{
    protected $model = PesquisaAcessoPublico::class;

    public function definition(): array
    {
        return [
            'pesquisa_id'   => Pesquisa::factory(),
            'sessao_token'  => Str::random(48),
            'ip'            => $this->faker->ipv4(),
            'user_agent'    => $this->faker->userAgent(),
            'respondido_em' => null,
        ];
    }
}
