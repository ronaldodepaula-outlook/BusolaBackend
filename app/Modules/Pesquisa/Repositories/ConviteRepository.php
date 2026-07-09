<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Modules\Pesquisa\Models\PesquisaConvite;
use Illuminate\Database\Eloquent\Collection;

class ConviteRepository
{
    public function listarPorPesquisa(int $pesquisaId): Collection
    {
        return PesquisaConvite::query()
            ->where('pesquisa_id', $pesquisaId)
            ->with('colaborador:id,nome,email,filial_id')
            ->get();
    }

    public function buscarPorToken(string $token): ?PesquisaConvite
    {
        return PesquisaConvite::query()->where('token', $token)->with('pesquisa')->first();
    }

    public function colaboradoresJaConvidados(int $pesquisaId): array
    {
        return PesquisaConvite::query()->where('pesquisa_id', $pesquisaId)->pluck('colaborador_id')->all();
    }
}
