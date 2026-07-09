<?php

namespace App\Modules\Pesquisa\Services;

use App\Modules\Pesquisa\Models\Colaborador;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Repositories\ConviteRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ConviteService
{
    public function __construct(
        private readonly ConviteRepository $conviteRepository,
    ) {
    }

    /**
     * Resolve o público-alvo da campanha em uma lista concreta de
     * colaboradores e gera um convite (token individual) para cada um que
     * ainda não tenha um. Idempotente — seguro chamar mais de uma vez para a
     * mesma campanha.
     *
     * O GHE do colaborador (via seu Setor) é resolvido e gravado como
     * snapshot no convite — usado só para agregação por grupo nos
     * resultados, nunca para ligar o conteúdo da resposta a uma pessoa.
     */
    public function gerarConvites(Pesquisa $pesquisa): int
    {
        $colaboradores = $this->resolverPublicoAlvo($pesquisa);
        $jaConvidados = $this->conviteRepository->colaboradoresJaConvidados($pesquisa->id);

        $criados = 0;
        foreach ($colaboradores as $colaborador) {
            if (in_array($colaborador->id, $jaConvidados, true)) {
                continue;
            }

            PesquisaConvite::create([
                'pesquisa_id'    => $pesquisa->id,
                'colaborador_id' => $colaborador->id,
                'ghe_id'         => $colaborador->setor?->ghe_id,
                'token'          => Str::random(48),
            ]);
            $criados++;
        }

        return $criados;
    }

    public function listar(int $pesquisaId): Collection
    {
        return $this->conviteRepository->listarPorPesquisa($pesquisaId);
    }

    private function resolverPublicoAlvo(Pesquisa $pesquisa): Collection
    {
        $publico = $pesquisa->publico()->get();

        if ($publico->isEmpty()) {
            return Colaborador::with('setor')->where('empresa_id', $pesquisa->empresa_id)->where('ativo', true)->get();
        }

        if ($publico->first()->filial_id !== null) {
            $filialIds = $publico->pluck('filial_id')->filter()->unique()->values();

            return Colaborador::with('setor')->whereIn('filial_id', $filialIds)->where('ativo', true)->get();
        }

        $colaboradorIds = $publico->pluck('colaborador_id')->filter()->unique()->values();

        return Colaborador::with('setor')->whereIn('id', $colaboradorIds)->where('ativo', true)->get();
    }
}
