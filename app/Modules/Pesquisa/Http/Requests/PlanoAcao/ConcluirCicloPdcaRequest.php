<?php

namespace App\Modules\Pesquisa\Http\Requests\PlanoAcao;

use App\Modules\Pesquisa\Enums\Eficacia;
use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use Illuminate\Validation\Rule;

class ConcluirCicloPdcaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'eficacia'             => ['required', Rule::in(array_map(fn ($e) => $e->value, Eficacia::cases()))],
            'necessita_nova_acao'  => 'sometimes|boolean',
        ];
    }
}
