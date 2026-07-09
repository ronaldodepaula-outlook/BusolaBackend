<?php

namespace App\Modules\Pesquisa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerguntaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'subcategoria_id'     => $this->subcategoria_id,
            'formulario_id'       => $this->formulario_id,
            'conceito_id'         => $this->conceito_id,
            'tipo_pergunta'       => $this->tipo_pergunta?->value,
            'texto'               => $this->texto,
            'descricao'           => $this->descricao,
            'obrigatoria'         => $this->obrigatoria,
            'permite_observacao'  => $this->permite_observacao,
            'permite_anexo'       => $this->permite_anexo,
            'ordem'               => $this->ordem,
            'ativo'               => $this->ativo,
            'conceito'            => $this->whenLoaded('conceito', fn () => new ConceitoResource($this->conceito)),
        ];
    }
}
