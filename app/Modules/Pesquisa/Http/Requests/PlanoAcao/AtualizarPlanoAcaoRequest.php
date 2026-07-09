<?php

namespace App\Modules\Pesquisa\Http\Requests\PlanoAcao;

use App\Modules\Pesquisa\Enums\StatusPlanoAcao;
use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use Illuminate\Validation\Rule;

class AtualizarPlanoAcaoRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'responsavel' => 'sometimes|nullable|string|max:150',
            'prazo'       => 'sometimes|nullable|string|max:30',
            'status'      => ['sometimes', Rule::in(array_map(fn ($c) => $c->value, StatusPlanoAcao::cases()))],
            'observacoes' => 'sometimes|nullable|string',
        ];
    }
}
