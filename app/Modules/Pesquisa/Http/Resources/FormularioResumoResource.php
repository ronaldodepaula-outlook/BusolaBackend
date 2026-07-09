<?php

namespace App\Modules\Pesquisa\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormularioResumoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'formulario_raiz_id' => $this->formulario_raiz_id,
            'nome'               => $this->nome,
            'codigo'             => $this->codigo,
            'status'             => $this->status?->value,
            'versao'             => $this->versao,
            'ativo'              => $this->ativo,
            'created_at'         => $this->created_at,
        ];
    }
}
