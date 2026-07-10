<?php

namespace App\Modules\Pesquisa\Http\Requests\Formulario;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\PadraoFormulario;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateFormularioRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        return [
            'nome'      => 'sometimes|required|string|max:150',
            'descricao' => 'nullable|string',
            'padrao_formulario_id' => 'nullable|integer|exists:pesq_padroes_formulario,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do formulário é obrigatório.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $padraoFormularioId = $this->input('padrao_formulario_id');
            if (! $padraoFormularioId) {
                return;
            }

            $formulario = Formulario::find($this->route('id'));
            $padrao = PadraoFormulario::find($padraoFormularioId);
            $visivel = $formulario && $padrao && $padrao->ativo
                && ($padrao->empresa_id === null || (int) $padrao->empresa_id === (int) $formulario->empresa_id);

            if (! $visivel) {
                $validator->errors()->add('padrao_formulario_id', 'Padrão de formulário inválido para este escopo.');
            }
        });
    }
}
