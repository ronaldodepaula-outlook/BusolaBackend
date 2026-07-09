<?php

namespace App\Modules\Pesquisa\Http\Requests\PlanoAcao;

use App\Modules\Pesquisa\Enums\FasePdca;
use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use Illuminate\Validation\Rule;

class AvancarFasePdcaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'fase'                 => ['required', Rule::in(array_map(fn ($f) => $f->value, FasePdca::cases()))],
            'evidencia_execucao'   => 'required_if:fase,verificar|nullable|string',
            'parecer_verificacao'  => 'required_if:fase,agir|nullable|string',
        ];
    }
}
