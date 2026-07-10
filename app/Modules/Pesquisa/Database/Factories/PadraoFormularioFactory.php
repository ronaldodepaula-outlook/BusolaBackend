<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Models\Empresa;
use App\Modules\Pesquisa\Models\PadraoFormulario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PadraoFormulario>
 */
class PadraoFormularioFactory extends Factory
{
    protected $model = PadraoFormulario::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nome'       => fake()->unique()->words(2, true),
            'descricao'  => fake()->sentence(),
            'ativo'      => true,
        ];
    }

    public function global(): self
    {
        return $this->state(['empresa_id' => null]);
    }
}
