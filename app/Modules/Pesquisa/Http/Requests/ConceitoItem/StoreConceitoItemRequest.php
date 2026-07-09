<?php

namespace App\Modules\Pesquisa\Http\Requests\ConceitoItem;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class StoreConceitoItemRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'descricao' => 'required|string|max:150',
            'valor'     => 'required|numeric',
            'cor'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'ordem'     => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição do item é obrigatória.',
            'valor.required'     => 'O valor do item é obrigatório.',
            'valor.numeric'      => 'O valor deve ser numérico.',
            'cor.regex'          => 'A cor deve estar no formato hexadecimal (#RRGGBB).',
        ];
    }
}
