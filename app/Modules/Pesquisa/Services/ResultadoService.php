<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\Contracts\MotorCalculoRiscoInterface;
use App\Modules\Pesquisa\Enums\TipoPergunta;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Ghe;
use App\Modules\Pesquisa\Models\PesquisaAcessoPublico;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use App\Modules\Pesquisa\Models\PesquisaRespostaItem;
use App\Modules\Pesquisa\Repositories\PesquisaRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agregações de resultado de uma campanha. Toda consulta aqui retorna dados
 * agregados (contagens/médias) — nunca uma resposta individual junto de
 * qualquer dado de quem respondeu, preservando o anonimato do conteúdo.
 *
 * A classificação de risco (Probabilidade × Severidade → farol) é calculada
 * tanto no agregado da empresa quanto por GHE, aplicando o quantitativo
 * mínimo de respondentes (pesquisa.minimo_respondentes) para agrupar GHEs
 * pequenos e preservar o anonimato, conforme a metodologia COPSOQ II.
 */
class ResultadoService
{
    private const NOME_GRUPO_AGREGADO = 'Grupo agregado (confidencialidade)';

    public function __construct(
        private readonly PesquisaRepository $pesquisaRepository,
        private readonly MotorCalculoRiscoResolver $motorResolver,
    ) {
    }

    public function resultados(int $pesquisaId, User $user): array
    {
        $pesquisa = $this->pesquisaRepository->buscarPorId($pesquisaId, $user);
        abort_if(! $pesquisa, 404, 'Campanha não encontrada.');

        $totalConvites    = PesquisaConvite::where('pesquisa_id', $pesquisaId)->count();
        $totalRespondidos = PesquisaConvite::where('pesquisa_id', $pesquisaId)->whereNotNull('respondido_em')->count();

        $formulario = Formulario::with(['categorias.subcategorias.perguntas.conceito.itens', 'padraoFormulario'])
            ->findOrFail($pesquisa->formulario_id);

        $motor = $this->motorResolver->resolver($formulario->padraoFormulario);

        $minimo = $pesquisa->minimo_respondentes ?? 5;
        $grupos = $this->resolverGruposDeGhe($pesquisaId, $minimo);

        $categoriasResultado = [];
        $resumoRisco = [];

        foreach ($formulario->categorias as $categoria) {
            $perguntasResultado = [];
            $somaMedias = 0.0;
            $countEscalas = 0;

            foreach ($categoria->subcategorias as $subcategoria) {
                foreach ($subcategoria->perguntas as $pergunta) {
                    $resultadoPergunta = $this->resultadoPergunta($pergunta, $pesquisaId);

                    if ($pergunta->tipo_pergunta === TipoPergunta::ESCALA && $resultadoPergunta['media'] !== null) {
                        $somaMedias += $resultadoPergunta['media'];
                        $countEscalas++;
                    }

                    $perguntasResultado[] = $resultadoPergunta;
                }
            }

            $media = $countEscalas > 0 ? round($somaMedias / $countEscalas, 2) : null;
            $severidade = $categoria->severidadeEfetiva();
            $risco = $this->classificarRisco($media, $severidade, $motor);

            if ($risco !== null) {
                $resumoRisco[$risco['nivel']->value] = ($resumoRisco[$risco['nivel']->value] ?? 0) + 1;
            }

            $categoriasResultado[] = [
                'id'                   => $categoria->id,
                'nome'                 => $categoria->nome,
                'categoria_referencia' => $categoria->categoria_referencia?->value,
                'media'                => $media,
                'severidade'           => $severidade,
                'risco'                => $risco,
                'grupos_ghe'           => $severidade
                    ? $this->riscoPorGrupoGhe($categoria->id, $severidade, $grupos, $motor)
                    : [],
                'perguntas'            => $perguntasResultado,
            ];
        }

        $totalAcessosGlobais    = PesquisaAcessoPublico::where('pesquisa_id', $pesquisaId)->count();
        $totalRespondidosGlobal = PesquisaAcessoPublico::where('pesquisa_id', $pesquisaId)->whereNotNull('respondido_em')->count();

        return [
            'pesquisa' => [
                'id'                  => $pesquisa->id,
                'nome'                => $pesquisa->nome,
                'status'              => $pesquisa->status->value,
                'minimo_respondentes' => $minimo,
            ],
            'taxa_resposta' => [
                'total_convites'    => $totalConvites,
                'total_respondidos' => $totalRespondidos,
                'percentual'        => $totalConvites > 0 ? round($totalRespondidos / $totalConvites * 100, 1) : 0.0,
            ],
            'acessos_globais' => [
                'total_sessoes' => $totalAcessosGlobais,
                'respondidas'   => $totalRespondidosGlobal,
            ],
            'grupos_ghe'  => $grupos->map(fn ($g) => ['nome' => $g['nome'], 'total_respostas' => $g['total_respostas']])->values(),
            'resumo_risco' => $resumoRisco,
            'categorias'  => $categoriasResultado,
            'matriz_risco' => $this->matrizRisco($categoriasResultado, $motor),
        ];
    }

    /**
     * Grade completa Probabilidade(1-5) × Severidade(1-5) para visualização
     * em matriz no dashboard: cada célula já traz o nível/farol que o motor
     * de cálculo desta campanha atribuiria (mesmo que nenhuma avaliação real
     * tenha caído nela, para o heatmap poder desenhar as 25 células), mais a
     * contagem de avaliações de risco (por Categoria×GHE, ou a agregada da
     * empresa quando não há grupos de GHE) que caíram em cada uma.
     *
     * @param  array<int, array>  $categoriasResultado
     * @return array{celulas: array<int, array>, nao_significativo: int}
     */
    private function matrizRisco(array $categoriasResultado, MotorCalculoRiscoInterface $motor): array
    {
        $celulas = [];
        for ($p = 1; $p <= 5; $p++) {
            for ($s = 1; $s <= 5; $s++) {
                $nivel = $motor->classificar($p, $s);
                $celulas["{$p}-{$s}"] = [
                    'probabilidade' => $p,
                    'severidade'    => $s,
                    'nivel'         => $nivel->value,
                    'nivel_label'   => $nivel->label(),
                    'farol_cor'     => $nivel->farolCor(),
                    'farol_emoji'   => $nivel->farolEmoji(),
                    'quantidade'    => 0,
                ];
            }
        }

        $naoSignificativo = 0;

        foreach ($categoriasResultado as $categoria) {
            $avaliacoes = ! empty($categoria['grupos_ghe'])
                ? array_column($categoria['grupos_ghe'], 'risco')
                : array_filter([$categoria['risco']]);

            foreach ($avaliacoes as $risco) {
                if ($risco === null) {
                    continue;
                }

                if ($risco['probabilidade'] === null) {
                    $naoSignificativo++;

                    continue;
                }

                $chave = "{$risco['probabilidade']}-{$risco['severidade']}";
                if (isset($celulas[$chave])) {
                    $celulas[$chave]['quantidade']++;
                }
            }
        }

        return [
            'celulas'           => array_values($celulas),
            'nao_significativo' => $naoSignificativo,
        ];
    }

    private function resultadoPergunta($pergunta, int $pesquisaId): array
    {
        $itens = PesquisaRespostaItem::where('pergunta_id', $pergunta->id)
            ->whereHas('resposta', fn ($q) => $q->where('pesquisa_id', $pesquisaId))
            ->get();

        $resultado = [
            'id'              => $pergunta->id,
            'texto'           => $pergunta->texto,
            'tipo'            => $pergunta->tipo_pergunta->value,
            'total_respostas' => $itens->count(),
            'media'           => null,
        ];

        if (in_array($pergunta->tipo_pergunta, [TipoPergunta::ESCALA, TipoPergunta::UNICA_ESCOLHA, TipoPergunta::MULTIPLA_ESCOLHA], true)) {
            $contagemPorItem = $itens->whereNotNull('conceito_item_id')->groupBy('conceito_item_id')->map->count();

            $resultado['distribuicao'] = ($pergunta->conceito?->itens ?? collect())->map(fn ($item) => [
                'descricao'  => $item->descricao,
                'cor'        => $item->cor,
                'quantidade' => $contagemPorItem[$item->id] ?? 0,
            ])->values();

            if ($pergunta->tipo_pergunta === TipoPergunta::ESCALA && $pergunta->conceito) {
                $valores = $itens->whereNotNull('conceito_item_id')->map(
                    fn ($it) => $pergunta->conceito->itens->firstWhere('id', $it->conceito_item_id)?->valor
                )->filter(fn ($v) => $v !== null);

                $resultado['media'] = $valores->isNotEmpty() ? round((float) $valores->avg(), 2) : null;
            }
        } elseif ($pergunta->tipo_pergunta === TipoPergunta::SIM_NAO) {
            $resultado['distribuicao'] = collect([
                ['descricao' => 'Sim', 'cor' => '#22c55e', 'quantidade' => $itens->where('valor_texto', 'sim')->count()],
                ['descricao' => 'Não', 'cor' => '#ef4444', 'quantidade' => $itens->where('valor_texto', 'nao')->count()],
            ]);
        } elseif ($pergunta->tipo_pergunta === TipoPergunta::TEXTO) {
            $resultado['respostas_texto'] = $itens->pluck('valor_texto')->filter()->values();
        }

        return $resultado;
    }

    /**
     * Classifica o risco de uma categoria a partir da média e da severidade
     * fixa, ou null quando a categoria não está vinculada a um fator de risco
     * oficial (sem severidade definida) ou não há respostas suficientes.
     */
    private function classificarRisco(?float $media, ?int $severidade, MotorCalculoRiscoInterface $motor): ?array
    {
        if ($media === null || $severidade === null) {
            return null;
        }

        $avaliacao = $motor->avaliar($media, $severidade);

        return [
            'probabilidade' => $avaliacao['probabilidade'],
            'severidade'    => $avaliacao['severidade'],
            'nivel'         => $avaliacao['nivel'],
            'nivel_label'   => $avaliacao['nivel']->label(),
            'farol'         => $avaliacao['nivel']->farolEmoji(),
            'farol_cor'     => $avaliacao['nivel']->farolCor(),
        ];
    }

    /**
     * Resolve os grupos de GHE visíveis nos resultados desta campanha: GHEs
     * com respondentes suficientes aparecem isolados; GHEs (ou respostas sem
     * GHE resolvido) abaixo do quantitativo mínimo são combinados em um único
     * grupo agregado, preservando o anonimato de grupos pequenos.
     *
     * @return Collection<int, array{tipo:string, nome:string, ghe_ids:int[], incluir_sem_ghe:bool, total_respostas:int}>
     */
    private function resolverGruposDeGhe(int $pesquisaId, int $minimo): Collection
    {
        $contagens = PesquisaResposta::where('pesquisa_id', $pesquisaId)
            ->select('ghe_id', DB::raw('count(*) as total'))
            ->groupBy('ghe_id')
            ->get();

        $ghes = Ghe::whereIn('id', $contagens->pluck('ghe_id')->filter())->get()->keyBy('id');

        $grupos = collect();
        $idsAgregados = [];
        $totalAgregado = 0;
        $incluirSemGhe = false;

        foreach ($contagens as $linha) {
            if ($linha->ghe_id === null) {
                $incluirSemGhe = true;
                $totalAgregado += $linha->total;

                continue;
            }

            if ($linha->total >= $minimo) {
                $grupos->push([
                    'tipo'            => 'ghe',
                    'ghe_id'          => $linha->ghe_id,
                    'nome'            => $ghes[$linha->ghe_id]->nome ?? "GHE #{$linha->ghe_id}",
                    'ghe_ids'         => [$linha->ghe_id],
                    'incluir_sem_ghe' => false,
                    'total_respostas' => $linha->total,
                ]);
            } else {
                $idsAgregados[] = $linha->ghe_id;
                $totalAgregado += $linha->total;
            }
        }

        if ($totalAgregado > 0) {
            $grupos->push([
                'tipo'            => 'agregado',
                'ghe_id'          => null,
                'nome'            => self::NOME_GRUPO_AGREGADO,
                'ghe_ids'         => $idsAgregados,
                'incluir_sem_ghe' => $incluirSemGhe,
                'total_respostas' => $totalAgregado,
            ]);
        }

        return $grupos;
    }

    /**
     * Calcula a média/risco de uma categoria isolada por grupo de GHE.
     */
    private function riscoPorGrupoGhe(int $categoriaId, int $severidade, Collection $grupos, MotorCalculoRiscoInterface $motor): array
    {
        $resultado = [];

        foreach ($grupos as $grupo) {
            $media = $this->mediaEscalaPorCategoria($categoriaId, $grupo);

            $resultado[] = [
                'ghe_id'          => $grupo['ghe_id'],
                'nome'            => $grupo['nome'],
                'total_respostas' => $grupo['total_respostas'],
                'media'           => $media,
                'risco'           => $this->classificarRisco($media, $severidade, $motor),
            ];
        }

        return $resultado;
    }

    private function mediaEscalaPorCategoria(int $categoriaId, array $grupo): ?float
    {
        $itens = PesquisaRespostaItem::query()
            ->whereNotNull('conceito_item_id')
            ->whereHas('pergunta', function ($q) use ($categoriaId) {
                $q->whereHas('subcategoria', fn ($sq) => $sq->where('categoria_id', $categoriaId))
                    ->where('tipo_pergunta', TipoPergunta::ESCALA->value);
            })
            ->whereHas('resposta', function ($q) use ($grupo) {
                $q->where(function ($inner) use ($grupo) {
                    if (! empty($grupo['ghe_ids'])) {
                        $inner->orWhereIn('ghe_id', $grupo['ghe_ids']);
                    }
                    if ($grupo['incluir_sem_ghe']) {
                        $inner->orWhereNull('ghe_id');
                    }
                });
            })
            ->with('conceitoItem')
            ->get();

        $valores = $itens->pluck('conceitoItem.valor')->filter(fn ($v) => $v !== null);

        return $valores->isNotEmpty() ? round((float) $valores->avg(), 2) : null;
    }
}
