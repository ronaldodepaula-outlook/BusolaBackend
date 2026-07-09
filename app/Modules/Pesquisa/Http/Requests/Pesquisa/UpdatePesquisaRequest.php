<?php

namespace App\Modules\Pesquisa\Http\Requests\Pesquisa;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class UpdatePesquisaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'        => 'sometimes|required|string|max:150',
            'descricao'   => 'nullable|string',
            'data_inicio' => 'nullable|date',
            'data_fim'    => 'nullable|date|after_or_equal:data_inicio',
            'anonima'     => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'          => 'O nome da campanha é obrigatório.',
            'data_fim.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
        ];
    }
}
