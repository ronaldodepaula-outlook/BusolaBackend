<?php

namespace App\Modules\Pesquisa\Http\Requests\RelatorioTecnico;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class GerarRelatorioTecnicoRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'responsavel_tecnico_nome'     => 'nullable|string|max:150',
            'responsavel_tecnico_registro' => 'nullable|string|max:60',
        ];
    }
}
