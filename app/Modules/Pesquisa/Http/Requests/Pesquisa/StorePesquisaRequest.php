<?php

namespace App\Modules\Pesquisa\Http\Requests\Pesquisa;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class StorePesquisaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'formulario_id' => 'required|integer|exists:pesq_formularios,id',
            'empresa_id'    => 'nullable|integer|exists:empresas,id',
        ];
    }

    public function messages(): array
    {
        return [
            'formulario_id.required' => 'Selecione o formulário da campanha.',
            'formulario_id.exists'   => 'Formulário inválido.',
        ];
    }
}
