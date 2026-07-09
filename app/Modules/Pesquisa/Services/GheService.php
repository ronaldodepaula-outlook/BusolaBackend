<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\Models\Ghe;
use Illuminate\Database\Eloquent\Collection;

class GheService
{
    public function listar(User $user): Collection
    {
        return Ghe::query()
            ->where('empresa_id', $this->empresaAlvo($user))
            ->with('setores')
            ->orderBy('nome')
            ->get();
    }

    public function buscar(int $id, User $user): Ghe
    {
        $ghe = Ghe::where('empresa_id', $this->empresaAlvo($user))->with('setores')->find($id);
        abort_if(! $ghe, 404, 'GHE não encontrado.');

        return $ghe;
    }

    public function criar(array $dados, User $user): Ghe
    {
        return Ghe::create([
            'empresa_id' => $this->empresaAlvo($user, $dados['empresa_id'] ?? null),
            'nome'       => $dados['nome'],
            'descricao'  => $dados['descricao'] ?? null,
            'ativo'      => $dados['ativo'] ?? true,
        ]);
    }

    public function atualizar(int $id, array $dados, User $user): Ghe
    {
        $ghe = $this->buscar($id, $user);

        $ghe->fill(array_filter([
            'nome'      => $dados['nome'] ?? null,
            'descricao' => $dados['descricao'] ?? null,
            'ativo'     => $dados['ativo'] ?? null,
        ], fn ($v) => $v !== null));
        $ghe->save();

        return $ghe;
    }

    public function excluir(int $id, User $user): void
    {
        $this->buscar($id, $user)->delete();
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
