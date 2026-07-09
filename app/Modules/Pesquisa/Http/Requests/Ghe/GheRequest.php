<?php

namespace App\Modules\Pesquisa\Http\Requests\Ghe;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class GheRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        $sometimes = $this->isMethod('PUT') || $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'nome'       => "{$sometimes}|string|max:150",
            'descricao'  => 'nullable|string',
            'ativo'      => 'sometimes|boolean',
        ];
    }
}
