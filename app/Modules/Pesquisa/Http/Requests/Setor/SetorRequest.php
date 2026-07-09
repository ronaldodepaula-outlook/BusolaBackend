<?php

namespace App\Modules\Pesquisa\Http\Requests\Setor;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class SetorRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        $sometimes = $this->isMethod('PUT') || $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'ghe_id'     => 'nullable|integer|exists:pesq_ghes,id',
            'nome'       => "{$sometimes}|string|max:150",
            'ativo'      => 'sometimes|boolean',
        ];
    }
}
