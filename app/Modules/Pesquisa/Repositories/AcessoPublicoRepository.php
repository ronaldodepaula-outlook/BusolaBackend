<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaAcessoPublico;

class AcessoPublicoRepository
{
    public function buscarPorPesquisaEToken(string $linkToken): ?Pesquisa
    {
        return Pesquisa::query()->where('link_publico_token', $linkToken)->first();
    }

    public function buscarOuCriarSessao(int $pesquisaId, string $sessaoToken, ?string $ip, ?string $userAgent): PesquisaAcessoPublico
    {
        return PesquisaAcessoPublico::firstOrCreate(
            ['pesquisa_id' => $pesquisaId, 'sessao_token' => $sessaoToken],
            ['ip' => $ip, 'user_agent' => $userAgent]
        );
    }
}
