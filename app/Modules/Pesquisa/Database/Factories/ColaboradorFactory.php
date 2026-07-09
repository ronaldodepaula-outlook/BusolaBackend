<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Models\Empresa;
use App\Modules\Pesquisa\Models\Colaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Colaborador>
 */
class ColaboradorFactory extends Factory
{
    protected $model = Colaborador::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nome'       => fake()->name(),
            'email'      => fake()->unique()->safeEmail(),
            'cargo'      => fake()->jobTitle(),
            'ativo'      => true,
            'origem'     => 'manual',
        ];
    }
}
