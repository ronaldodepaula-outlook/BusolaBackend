<?php

namespace App\Modules\Pesquisa\Http\Requests\Categoria;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use App\Modules\Pesquisa\Support\FatorRiscoReferenciaResolver;
use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'                 => 'sometimes|required|string|max:150',
            'descricao'            => 'nullable|string',
            'categoria_referencia' => ['nullable', Rule::in(FatorRiscoReferenciaResolver::todosOsValores())],
            'severidade'           => 'nullable|integer|min:1|max:5',
            'ordem'                => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da categoria é obrigatório.',
        ];
    }
}
