<?php

namespace App\Modules\Pesquisa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConceitoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'empresa_id'  => $this->empresa_id,
            'nome'        => $this->nome,
            'descricao'   => $this->descricao,
            'tipo'        => $this->tipo?->value,
            'ativo'       => $this->ativo,
            'total_itens' => $this->whenCounted('itens'),
            'itens'       => ConceitoItemResource::collection($this->whenLoaded('itens')),
        ];
    }
}
