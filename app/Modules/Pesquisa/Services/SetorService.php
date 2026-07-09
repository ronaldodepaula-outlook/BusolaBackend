<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\Models\Setor;
use App\Modules\Pesquisa\Models\UsuarioSetor;
use Illuminate\Database\Eloquent\Collection;

/**
 * CRUD simples de Setor, escopado por empresa. Diferente de
 * Formulario/Categoria/Pergunta, Setor não tem árvore de versionamento — por
 * isso não usa o padrão DTO/Repository mais pesado daquele fluxo.
 */
class SetorService
{
    public function listar(User $user): Collection
    {
        return Setor::query()
            ->where('empresa_id', $this->empresaAlvo($user))
            ->with('ghe')
            ->orderBy('nome')
            ->get();
    }

    public function buscar(int $id, User $user): Setor
    {
        $setor = Setor::where('empresa_id', $this->empresaAlvo($user))->find($id);
        abort_if(! $setor, 404, 'Setor não encontrado.');

        return $setor;
    }

    public function criar(array $dados, User $user): Setor
    {
        return Setor::create([
            'empresa_id' => $this->empresaAlvo($user, $dados['empresa_id'] ?? null),
            'ghe_id'     => $dados['ghe_id'] ?? null,
            'nome'       => $dados['nome'],
            'ativo'      => $dados['ativo'] ?? true,
        ]);
    }

    public function atualizar(int $id, array $dados, User $user): Setor
    {
        $setor = $this->buscar($id, $user);

        $setor->fill(array_filter([
            'ghe_id' => $dados['ghe_id'] ?? null,
            'nome'   => $dados['nome'] ?? null,
            'ativo'  => $dados['ativo'] ?? null,
        ], fn ($v) => $v !== null));
        $setor->save();

        return $setor;
    }

    public function excluir(int $id, User $user): void
    {
        $this->buscar($id, $user)->delete();
    }

    /** Atribui (ou remove, com setorId null) o setor de um colaborador. */
    public function definirSetorDoUsuario(int $userId, ?int $setorId, User $user): void
    {
        if ($setorId === null) {
            UsuarioSetor::where('user_id', $userId)->delete();

            return;
        }

        $setor = $this->buscar($setorId, $user);

        UsuarioSetor::updateOrCreate(
            ['user_id' => $userId],
            ['setor_id' => $setor->id]
        );
    }

    public function gheDoUsuario(int $userId): ?int
    {
        $mapeamento = UsuarioSetor::with('setor')->where('user_id', $userId)->first();

        return $mapeamento?->setor?->ghe_id;
    }

    private function empresaAlvo(User $user, ?int $empresaIdSolicitada = null): int
    {
        if ($user->isSuperAdmin() && $empresaIdSolicitada) {
            return $empresaIdSolicitada;
        }

        abort_if(! $user->empresa_id, 422, 'Usuário não está vinculado a uma empresa.');

        return $user->empresa_id;
    }
}
