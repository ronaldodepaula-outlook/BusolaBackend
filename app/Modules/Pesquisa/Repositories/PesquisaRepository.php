<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Models\User;
use App\Modules\Pesquisa\Models\Pesquisa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PesquisaRepository
{
    public function paginar(array $filtros, User $user, int $porPagina = 15): LengthAwarePaginator
    {
        $query = Pesquisa::query()->visiveisPara($user)->with('formulario:id,nome,codigo,status,ativo');

        if (! empty($filtros['empresa_id']) && $user->isSuperAdmin()) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        return $query->orderByDesc('id')->paginate($porPagina);
    }

    public function buscarPorId(int $id, User $user): ?Pesquisa
    {
        return Pesquisa::query()->visiveisPara($user)->with(['formulario:id,nome,codigo,status,ativo', 'publico'])->find($id);
    }
}
