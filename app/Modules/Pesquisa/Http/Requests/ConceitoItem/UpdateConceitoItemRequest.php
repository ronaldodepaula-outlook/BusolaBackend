<?php

namespace App\Modules\Pesquisa\Http\Requests\ConceitoItem;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class UpdateConceitoItemRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'descricao' => 'sometimes|required|string|max:150',
            'valor'     => 'sometimes|required|numeric',
            'cor'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'ordem'     => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição do item é obrigatória.',
            'valor.numeric'      => 'O valor deve ser numérico.',
            'cor.regex'          => 'A cor deve estar no formato hexadecimal (#RRGGBB).',
        ];
    }
}
