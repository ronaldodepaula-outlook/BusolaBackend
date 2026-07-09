<?php

namespace App\Modules\Pesquisa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormularioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'formulario_raiz_id'  => $this->formulario_raiz_id,
            'empresa_id'          => $this->empresa_id,
            'nome'                => $this->nome,
            'codigo'              => $this->codigo,
            'descricao'           => $this->descricao,
            'status'              => $this->status?->value,
            'tipo'                => $this->tipo?->value,
            'versao'              => $this->versao,
            'ativo'               => $this->ativo,
            'created_by'          => $this->created_by,
            'updated_by'          => $this->updated_by,
            'total_categorias'    => $this->whenCounted('categorias'),
            'categorias'          => CategoriaResource::collection($this->whenLoaded('categorias')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
