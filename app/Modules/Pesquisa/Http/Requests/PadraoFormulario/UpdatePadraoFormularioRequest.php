<?php

namespace App\Modules\Pesquisa\Http\Requests\PadraoFormulario;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class UpdatePadraoFormularioRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'      => 'sometimes|required|string|max:150',
            'descricao' => 'nullable|string',
            'ativo'     => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do padrão é obrigatório.',
        ];
    }
}
