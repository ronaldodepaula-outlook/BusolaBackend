<?php

namespace App\Modules\Pesquisa\Http\Requests\Categoria;

use App\Modules\Pesquisa\Enums\CategoriaReferencia;
use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'                 => 'sometimes|required|string|max:150',
            'descricao'            => 'nullable|string',
            'categoria_referencia' => ['nullable', Rule::in(array_map(fn ($c) => $c->value, CategoriaReferencia::cases()))],
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
