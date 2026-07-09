<?php

namespace App\Modules\Pesquisa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesquisaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'empresa_id'    => $this->empresa_id,
            'formulario_id' => $this->formulario_id,
            'formulario'    => $this->whenLoaded('formulario', fn () => [
                'id'     => $this->formulario->id,
                'nome'   => $this->formulario->nome,
                'codigo' => $this->formulario->codigo,
            ]),
            'nome'          => $this->nome,
            'descricao'     => $this->descricao,
            'data_inicio'   => $this->data_inicio?->toDateString(),
            'data_fim'      => $this->data_fim?->toDateString(),
            'anonima'       => $this->anonima,
            'status'        => $this->status?->value,
            'criado_por'    => $this->criado_por,
            'link_publico_token' => $this->link_publico_token,
            'publico'       => $this->whenLoaded('publico', fn () => [
                'tipo' => $this->publico->isEmpty()
                    ? 'toda_empresa'
                    : ($this->publico->first()->filial_id ? 'filiais' : 'colaboradores'),
                'filial_ids'      => $this->publico->pluck('filial_id')->filter()->values(),
                'colaborador_ids' => $this->publico->pluck('colaborador_id')->filter()->values(),
            ]),
            'created_at'    => $this->created_at,
        ];
    }
}
