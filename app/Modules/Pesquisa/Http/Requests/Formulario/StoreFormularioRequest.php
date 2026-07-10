<?php

namespace App\Modules\Pesquisa\Http\Requests\Formulario;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use App\Modules\Pesquisa\Models\PadraoFormulario;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class StoreFormularioRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'      => 'required|string|max:150',
            'codigo'    => 'required|string|max:50|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'descricao' => 'nullable|string',
            'tipo'      => 'required|in:global,empresa',
            'empresa_id' => 'nullable|integer|exists:empresas,id',
            'padrao_formulario_id' => 'nullable|integer|exists:pesq_padroes_formulario,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'   => 'O nome do formulário é obrigatório.',
            'codigo.required' => 'O código do formulário é obrigatório.',
            'codigo.regex'    => 'O código deve conter apenas letras minúsculas, números e hífens.',
            'tipo.required'   => 'O tipo do formulário é obrigatório.',
            'tipo.in'         => 'Tipo inválido. Use "global" ou "empresa".',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $user = $this->authUser();
            $tipo = $this->input('tipo');

            if ($tipo === 'global' && (! $user || ! $user->isSuperAdmin())) {
                $validator->errors()->add('tipo', 'Apenas o superadmin pode criar formulários globais.');
                return;
            }

            $empresaIdAlvo = null;
            if ($tipo === 'empresa') {
                $empresaId = $this->input('empresa_id');
                $empresaIdAlvo = $user->isSuperAdmin() ? $empresaId : $user->empresa_id;

                if (! $user->isSuperAdmin()) {
                    if ($empresaId && (int) $empresaId !== (int) $user->empresa_id) {
                        $validator->errors()->add('empresa_id', 'Você só pode criar formulários para a sua própria empresa.');
                    }
                } elseif (! $empresaId) {
                    $validator->errors()->add('empresa_id', 'Informe a empresa para um formulário do tipo "empresa".');
                }
            }

            $padraoFormularioId = $this->input('padrao_formulario_id');
            if ($padraoFormularioId) {
                $padrao = PadraoFormulario::find($padraoFormularioId);
                $visivel = $padrao && $padrao->ativo && ($padrao->empresa_id === null || (int) $padrao->empresa_id === (int) $empresaIdAlvo);

                if (! $visivel) {
                    $validator->errors()->add('padrao_formulario_id', 'Padrão de formulário inválido para este escopo.');
                }
            }
        });
    }
}
