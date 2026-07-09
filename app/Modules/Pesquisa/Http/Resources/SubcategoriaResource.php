<?php

namespace App\Modules\Pesquisa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'categoria_id'    => $this->categoria_id,
            'formulario_id'   => $this->formulario_id,
            'nome'            => $this->nome,
            'descricao'       => $this->descricao,
            'ordem'           => $this->ordem,
            'ativo'           => $this->ativo,
            'total_perguntas' => $this->whenCounted('perguntas'),
            'perguntas'       => PerguntaResource::collection($this->whenLoaded('perguntas')),
        ];
    }
}
