<?php

namespace App\Modules\Pesquisa\Http\Requests\Colaborador;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class ImportarColaboradoresRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'conteudo_csv' => 'required|string',
            'empresa_id'   => 'nullable|integer|exists:empresas,id',
        ];
    }

    public function messages(): array
    {
        return [
            'conteudo_csv.required' => 'Selecione um arquivo CSV para importar.',
        ];
    }
}
