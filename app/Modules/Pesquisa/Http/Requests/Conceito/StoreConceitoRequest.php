<?php

namespace App\Modules\Pesquisa\Http\Requests\Conceito;

use App\Modules\Pesquisa\Enums\TipoConceito;
use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;

class StoreConceitoRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'       => 'required|string|max:150',
            'descricao'  => 'nullable|string',
            'tipo'       => ['required', Rule::in(array_column(TipoConceito::cases(), 'value'))],
            'empresa_id' => 'nullable|integer|exists:empresas,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do conceito é obrigatório.',
            'tipo.required' => 'O tipo do conceito é obrigatório.',
            'tipo.in'       => 'Tipo de conceito inválido.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $user = $this->authUser();

            if ($user->isSuperAdmin()) {
                return;
            }

            // Usuários comuns sempre criam o conceito na própria empresa (forçado
            // pelo Service) — só é erro se tentarem explicitamente indicar OUTRA empresa.
            $empresaId = $this->input('empresa_id');

            if ($empresaId && (int) $empresaId !== (int) $user->empresa_id) {
                $validator->errors()->add('empresa_id', 'Você só pode criar conceitos para a sua própria empresa.');
            }
        });
    }
}
