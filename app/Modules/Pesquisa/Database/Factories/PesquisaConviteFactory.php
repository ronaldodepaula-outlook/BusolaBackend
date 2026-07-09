<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Models\Colaborador;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PesquisaConvite>
 */
class PesquisaConviteFactory extends Factory
{
    protected $model = PesquisaConvite::class;

    public function definition(): array
    {
        return [
            'pesquisa_id'    => Pesquisa::factory(),
            'colaborador_id' => Colaborador::factory(),
            'token'          => Str::random(48),
            'respondido_em'  => null,
        ];
    }
}
