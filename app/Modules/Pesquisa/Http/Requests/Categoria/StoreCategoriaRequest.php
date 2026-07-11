<?php

namespace App\Modules\Pesquisa\Http\Requests\Categoria;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use App\Modules\Pesquisa\Support\FatorRiscoReferenciaResolver;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'                 => 'required|string|max:150',
            'descricao'            => 'nullable|string',
            // Aceita os valores de qualquer um dos padrões de cálculo (NR-1 completo ou COPSOQ resumido) —
            // qual deles é válido para este formulário depende do Padrão de Formulário selecionado.
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
