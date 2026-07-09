<?php

namespace App\Modules\Pesquisa\Database\Factories;

use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use App\Modules\Pesquisa\Models\PesquisaRespostaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesquisaRespostaItem>
 */
class PesquisaRespostaItemFactory extends Factory
{
    protected $model = PesquisaRespostaItem::class;

    public function definition(): array
    {
        return [
            'pesquisa_resposta_id' => PesquisaResposta::factory(),
            'pergunta_id'          => Pergunta::factory(),
            'conceito_item_id'     => null,
            'valor_texto'          => $this->faker->sentence(),
            'valor_numero'         => null,
            'observacao'           => null,
        ];
    }
}
