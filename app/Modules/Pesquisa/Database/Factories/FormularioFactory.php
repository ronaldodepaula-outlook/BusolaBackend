<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Enums\TipoFormulario;
use App\Modules\Pesquisa\Models\Formulario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Formulario>
 */
class FormularioFactory extends Factory
{
    protected $model = Formulario::class;

    public function definition(): array
    {
        return [
            'formulario_raiz_id' => null,
            'empresa_id'         => null,
            'nome'               => fake()->sentence(3),
            'codigo'             => fake()->unique()->slug(3),
            'descricao'          => fake()->sentence(),
            'status'             => StatusFormulario::RASCUNHO,
            'tipo'               => TipoFormulario::GLOBAL,
            'versao'             => 1,
            'ativo'              => true,
        ];
    }

    public function daEmpresa(int $empresaId): static
    {
        return $this->state(fn (array $attributes) => [
            'empresa_id' => $empresaId,
            'tipo'       => TipoFormulario::EMPRESA,
        ]);
    }
}
