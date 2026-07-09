<?php

namespace App\Modules\Pesquisa\Http\Requests\Pesquisa;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use Illuminate\Validation\Rule;

class DefinirPublicoRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'tipo'    => ['required', Rule::in(['toda_empresa', 'filiais', 'colaboradores'])],
            'ids'     => 'required_if:tipo,filiais,colaboradores|array',
            'ids.*'   => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required'    => 'Selecione o tipo de público-alvo.',
            'tipo.in'          => 'Tipo de público-alvo inválido.',
            'ids.required_if'  => 'Selecione ao menos um item para este tipo de público-alvo.',
        ];
    }
}
