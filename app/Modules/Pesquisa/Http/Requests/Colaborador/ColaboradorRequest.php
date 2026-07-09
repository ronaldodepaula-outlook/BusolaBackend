<?php

namespace App\Modules\Pesquisa\Http\Requests\Colaborador;

use App\Modules\Pesquisa\Http\Requests\PesquisaFormRequest;

class ColaboradorRequest extends PesquisaFormRequest
{
    public function rules(): array
    {
        $sometimes = $this->isMethod('PUT') || $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'empresa_id'      => 'nullable|integer|exists:empresas,id',
            'filial_id'       => 'nullable|integer|exists:filiais,id',
            'setor_id'        => 'nullable|integer|exists:pesq_setores,id',
            'matricula'       => 'nullable|string|max:40',
            'nome'            => "{$sometimes}|string|max:150",
            'email'           => 'nullable|email|max:150',
            'cargo'           => 'nullable|string|max:100',
            'ativo'           => 'sometimes|boolean',
            'cpf'             => ['nullable', 'regex:/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/'],
            'data_nascimento' => 'nullable|date|before:today',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do colaborador é obrigatório.',
            'cpf.regex'     => 'Informe um CPF válido (com ou sem pontuação).',
        ];
    }
}
