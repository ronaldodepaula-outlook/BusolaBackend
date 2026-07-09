<?php

namespace App\Modules\Pesquisa\Http\Requests\Formulario;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class UpdateFormularioRequest extends PesquisaFormRequest
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
            'nome.required' => 'O nome do formulário é obrigatório.',
        ];
    }
}
