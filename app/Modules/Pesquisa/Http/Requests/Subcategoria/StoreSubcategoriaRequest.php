<?php

namespace App\Modules\Pesquisa\Http\Requests\Subcategoria;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class StoreSubcategoriaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'      => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'ordem'     => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da subcategoria é obrigatório.',
        ];
    }
}
