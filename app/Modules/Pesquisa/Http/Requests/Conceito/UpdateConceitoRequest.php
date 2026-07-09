<?php

namespace App\Modules\Pesquisa\Http\Requests\Conceito;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class UpdateConceitoRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'      => 'sometimes|required|string|max:150',
            'descricao' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do conceito é obrigatório.',
        ];
    }
}
