<?php

namespace App\Modules\Pesquisa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'formulario_id'        => $this->formulario_id,
            'nome'                 => $this->nome,
            'descricao'            => $this->descricao,
            'categoria_referencia' => $this->categoria_referencia?->value,
            'severidade'           => $this->severidadeEfetiva(),
            'ordem'                => $this->ordem,
            'ativo'                => $this->ativo,
            'total_subcategorias' => $this->whenCounted('subcategorias'),
            'subcategorias'       => SubcategoriaResource::collection($this->whenLoaded('subcategorias')),
        ];
    }
}
