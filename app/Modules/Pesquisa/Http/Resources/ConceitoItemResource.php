<?php

namespace App\Modules\Pesquisa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConceitoItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'conceito_id' => $this->conceito_id,
            'descricao'   => $this->descricao,
            'valor'       => (float) $this->valor,
            'cor'         => $this->cor,
            'ordem'       => $this->ordem,
        ];
    }
}
