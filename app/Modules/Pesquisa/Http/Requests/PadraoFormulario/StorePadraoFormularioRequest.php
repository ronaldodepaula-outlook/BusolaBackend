<?php

namespace App\Modules\Pesquisa\Http\Requests\PadraoFormulario;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StorePadraoFormularioRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'       => 'required|string|max:150',
            'descricao'  => 'nullable|string',
            'tipo'       => 'required|in:global,empresa',
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'ativo'      => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do padrão é obrigatório.',
            'tipo.required' => 'O tipo do padrão é obrigatório.',
            'tipo.in'       => 'Tipo inválido. Use "global" ou "empresa".',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $user = $this->authUser();
            $tipo = $this->input('tipo');

            if ($tipo === 'global' && ! $user->isSuperAdmin()) {
                $validator->errors()->add('tipo', 'Apenas o superadmin pode criar padrões globais.');
                return;
            }

            if ($tipo === 'empresa') {
                $empresaId = $this->input('empresa_id');

                if (! $user->isSuperAdmin()) {
                    if ($empresaId && (int) $empresaId !== (int) $user->empresa_id) {
                        $validator->errors()->add('empresa_id', 'Você só pode criar padrões para a sua própria empresa.');
                    }
                } elseif (! $empresaId) {
                    $validator->errors()->add('empresa_id', 'Informe a empresa para um padrão do tipo "empresa".');
                }
            }
        });
    }
}
