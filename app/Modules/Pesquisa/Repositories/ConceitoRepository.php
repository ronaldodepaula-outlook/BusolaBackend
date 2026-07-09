<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Models\User;
use App\Modules\Pesquisa\Models\Conceito;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConceitoRepository
{
    public function paginar(array $filtros, User $user, int $porPagina = 15): LengthAwarePaginator
    {
        $query = Conceito::query()->visiveisPara($user)->withCount('itens');

        if (! empty($filtros['empresa_id']) && $user->isSuperAdmin()) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (! empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (! empty($filtros['search'])) {
            $query->where('nome', 'like', '%'.$filtros['search'].'%');
        }

        return $query->orderBy('nome')->paginate($porPagina);
    }

    public function buscarPorId(int $id, User $user): ?Conceito
    {
        return Conceito::query()->visiveisPara($user)->with('itens')->find($id);
    }
}
