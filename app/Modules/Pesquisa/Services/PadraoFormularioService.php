<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\Models\PadraoFormulario;
use Illuminate\Database\Eloquent\Collection;

/**
 * CRUD do "Padrão de Formulário" — a norma/metodologia que um formulário
 * segue (ex.: COPSOQ II, NR-1, ISO 45003 ou um padrão específico de uma
 * empresa). Mesma convenção de escopo global/empresa já usada em Formulario:
 * empresa_id nulo = padrão global, visível e selecionável por todas as
 * empresas; caso contrário, só a empresa dona o enxerga/seleciona.
 */
class PadraoFormularioService
{
    public function listar(User $user, array $filtros = []): Collection
    {
        $query = PadraoFormulario::query()->visiveisPara($user);

        if (! empty($filtros['empresa_id']) && $user->isSuperAdmin()) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (! empty($filtros['apenas_ativos'])) {
            $query->where('ativo', true);
        }

        return $query->orderBy('nome')->get();
    }

    public function buscar(int $id, User $user): PadraoFormulario
    {
        $padrao = PadraoFormulario::query()->visiveisPara($user)->find($id);
        abort_if(! $padrao, 404, 'Padrão de formulário não encontrado.');

        return $padrao;
    }

    public function criar(array $dados, User $user): PadraoFormulario
    {
        $empresaId = $dados['tipo'] === 'global'
            ? null
            : ($user->isSuperAdmin() ? ($dados['empresa_id'] ?? null) : $user->empresa_id);

        abort_if(
            $this->existeNomeNoEscopo($dados['nome'], $empresaId),
            422,
            'Já existe um padrão com este nome neste escopo.'
        );

        return PadraoFormulario::create([
            'empresa_id' => $empresaId,
            'nome'       => $dados['nome'],
            'descricao'  => $dados['descricao'] ?? null,
            'ativo'      => $dados['ativo'] ?? true,
            'created_by' => $user->id,
        ]);
    }

    public function atualizar(int $id, array $dados, User $user): PadraoFormulario
    {
        $padrao = $this->buscar($id, $user);

        if (isset($dados['nome']) && $dados['nome'] !== $padrao->nome) {
            abort_if(
                $this->existeNomeNoEscopo($dados['nome'], $padrao->empresa_id, $padrao->id),
                422,
                'Já existe um padrão com este nome neste escopo.'
            );
        }

        $padrao->fill(array_filter([
            'nome'      => $dados['nome'] ?? null,
            'descricao' => $dados['descricao'] ?? null,
        ], fn ($v) => $v !== null));

        if (array_key_exists('ativo', $dados)) {
            $padrao->ativo = $dados['ativo'];
        }

        $padrao->save();

        return $padrao;
    }

    public function excluir(int $id, User $user): void
    {
        $this->buscar($id, $user)->delete();
    }

    private function existeNomeNoEscopo(string $nome, ?int $empresaId, ?int $ignorarId = null): bool
    {
        return PadraoFormulario::query()
            ->where('nome', $nome)
            ->when($empresaId === null, fn ($q) => $q->whereNull('empresa_id'))
            ->when($empresaId !== null, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists();
    }
}
