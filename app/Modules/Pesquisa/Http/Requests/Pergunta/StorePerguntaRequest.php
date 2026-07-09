<?php

namespace App\Modules\Pesquisa\Http\Requests\Pergunta;

use App\Modules\Pesquisa\Enums\TipoPergunta;
use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;

class StorePerguntaRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'tipo_pergunta'       => ['required', Rule::in(array_column(TipoPergunta::cases(), 'value'))],
            'texto'               => 'required|string|max:500',
            'descricao'           => 'nullable|string',
            'obrigatoria'         => 'boolean',
            'permite_observacao'  => 'boolean',
            'permite_anexo'       => 'boolean',
            'conceito_id'         => 'nullable|integer|exists:pesq_conceitos,id',
            'ordem'               => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_pergunta.required' => 'O tipo da pergunta é obrigatório.',
            'tipo_pergunta.in'       => 'Tipo de pergunta inválido.',
            'texto.required'         => 'O texto da pergunta é obrigatório.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $tipo = TipoPergunta::tryFrom((string) $this->input('tipo_pergunta'));

            if ($tipo?->exigeConceito() && ! $this->filled('conceito_id')) {
                $validator->errors()->add(
                    'conceito_id',
                    'Um conceito de avaliação é obrigatório para perguntas do tipo "'.$tipo->value.'".'
                );
            }
        });
    }
}
