<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\Enums\Eficacia;
use App\Modules\Pesquisa\Enums\FasePdca;
use App\Modules\Pesquisa\Enums\NivelRisco;
use App\Modules\Pesquisa\Enums\StatusPlanoAcao;
use App\Modules\Pesquisa\Enums\TipoControle;
use App\Modules\Pesquisa\Models\PlanoAcao;
use App\Modules\Pesquisa\Models\PlanoAcaoTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Gera e acompanha o Plano de Ação de uma campanha a partir da classificação
 * de risco calculada pelo ResultadoService (categoria × GHE) e da biblioteca
 * de templates (PlanoAcaoTemplate), seguindo a aba BASE_ACAO da planilha de
 * referência: cada categoria/nível de risco/tipo de controle gera uma ação.
 */
class PlanoAcaoService
{
    public function __construct(
        private readonly ResultadoService $resultadoService,
    ) {
    }

    public function listar(int $pesquisaId, User $user): Collection
    {
        // Garante visibilidade/tenant scoping reaproveitando a mesma checagem do resultado.
        $this->resultadoService->resultados($pesquisaId, $user);

        return PlanoAcao::where('pesquisa_id', $pesquisaId)
            ->with(['categoria', 'ghe', 'template'])
            ->orderBy('categoria_id')
            ->orderBy('tipo_controle')
            ->get();
    }

    /**
     * (Re)gera as ações do plano a partir do resultado atual da campanha.
     * Idempotente: uma ação já existente para a mesma combinação
     * categoria/GHE/tipo de controle é atualizada, não duplicada.
     *
     * @return array{geradas: int, atualizadas: int}
     */
    public function gerar(int $pesquisaId, User $user): array
    {
        $resultado = $this->resultadoService->resultados($pesquisaId, $user);

        $geradas = 0;
        $atualizadas = 0;

        DB::transaction(function () use ($resultado, $pesquisaId, &$geradas, &$atualizadas) {
            foreach ($resultado['categorias'] as $categoria) {
                if (empty($categoria['categoria_referencia']) || empty($categoria['grupos_ghe'])) {
                    continue;
                }

                foreach ($categoria['grupos_ghe'] as $grupo) {
                    /** @var NivelRisco|null $nivel */
                    $nivel = $grupo['risco']['nivel'] ?? null;
                    $nivelBase = $nivel?->nivelBaseAcao();

                    if ($nivelBase === null) {
                        continue; // sem exposição significativa: sem plano de ação específico
                    }

                    foreach (TipoControle::cases() as $tipoControle) {
                        $template = PlanoAcaoTemplate::where('categoria_referencia', $categoria['categoria_referencia'])
                            ->where('nivel_base_acao', $nivelBase->value)
                            ->where('tipo_controle', $tipoControle->value)
                            ->first();

                        if (! $template) {
                            continue;
                        }

                        $planoAcao = PlanoAcao::updateOrCreate(
                            [
                                'pesquisa_id'   => $pesquisaId,
                                'categoria_id'  => $categoria['id'],
                                'ghe_id'        => $grupo['ghe_id'],
                                'tipo_controle' => $tipoControle->value,
                            ],
                            [
                                'template_id'   => $template->id,
                                'nivel_risco'   => $nivel->value,
                                'farol'         => $nivel->farolEmoji(),
                                'acao'          => $template->textoCompleto(),
                                'como_executar' => $template->como_executar,
                                'evidencia'     => $template->evidencia,
                                'responsavel'   => $template->responsavel_padrao,
                                'prazo'         => $template->prazo,
                            ]
                        );

                        $planoAcao->wasRecentlyCreated ? $geradas++ : $atualizadas++;
                    }
                }
            }
        });

        return ['geradas' => $geradas, 'atualizadas' => $atualizadas];
    }

    public function atualizar(int $id, array $dados, User $user): PlanoAcao
    {
        $planoAcao = PlanoAcao::with('pesquisa')->findOrFail($id);
        $this->resultadoService->resultados($planoAcao->pesquisa_id, $user);

        $planoAcao->fill(array_filter([
            'responsavel'  => $dados['responsavel'] ?? null,
            'prazo'        => $dados['prazo'] ?? null,
            'status'       => $dados['status'] ?? null,
            'observacoes'  => $dados['observacoes'] ?? null,
        ], fn ($v) => $v !== null));

        if (($dados['status'] ?? null) === 'concluido' && $planoAcao->concluido_em === null) {
            $planoAcao->concluido_em = now();
        }

        $planoAcao->save();

        return $planoAcao;
    }

    /**
     * Avança a ação para a próxima fase do ciclo PDCA (Planejar → Executar →
     * Verificar → Agir), exigindo a evidência/parecer correspondente e sem
     * permitir pular etapas.
     */
    public function avancarFase(int $id, string $novaFaseValor, array $dados, User $user): PlanoAcao
    {
        $planoAcao = PlanoAcao::with('pesquisa')->findOrFail($id);
        $this->resultadoService->resultados($planoAcao->pesquisa_id, $user);

        $novaFase = FasePdca::from($novaFaseValor);
        $esperada = $planoAcao->fase_pdca->proxima();

        abort_if($esperada === null, 409, 'Esta ação já está na fase final (Agir) — use a conclusão do ciclo.');
        abort_if(
            $novaFase !== $esperada,
            409,
            "Não é possível pular etapas do PDCA: a próxima fase esperada é \"{$esperada->label()}\"."
        );

        match ($novaFase) {
            FasePdca::EXECUTAR => null,
            FasePdca::VERIFICAR => $this->registrarExecucao($planoAcao, $dados),
            FasePdca::AGIR => $this->registrarVerificacao($planoAcao, $dados, $user),
            FasePdca::PLANEJAR => abort(409, 'Transição inválida.'),
        };

        $planoAcao->fase_pdca = $novaFase;
        if ($novaFase === FasePdca::EXECUTAR && $planoAcao->status === StatusPlanoAcao::PENDENTE) {
            $planoAcao->status = StatusPlanoAcao::EM_ANDAMENTO;
        }
        $planoAcao->save();

        return $planoAcao->fresh(['categoria', 'ghe', 'template', 'verificadoPor']);
    }

    /**
     * Conclui o ciclo PDCA a partir da fase Agir, registrando a eficácia
     * percebida. Se a ação não foi (totalmente) eficaz, um novo ciclo é
     * aberto automaticamente — a ação volta para "Planejar" com um contador
     * de ciclo incrementado, preservando o ciclo anterior em historico_pdca.
     */
    public function concluirCiclo(int $id, array $dados, User $user): PlanoAcao
    {
        $planoAcao = PlanoAcao::with('pesquisa')->findOrFail($id);
        $this->resultadoService->resultados($planoAcao->pesquisa_id, $user);

        abort_if($planoAcao->fase_pdca !== FasePdca::AGIR, 409, 'Só é possível concluir o ciclo a partir da fase Agir.');
        abort_if(empty($dados['eficacia']), 422, 'Informe a eficácia da ação para concluir o ciclo.');

        $eficacia = Eficacia::from($dados['eficacia']);
        $necessitaNovaAcao = array_key_exists('necessita_nova_acao', $dados)
            ? (bool) $dados['necessita_nova_acao']
            : $eficacia !== Eficacia::EFICAZ;

        $planoAcao->eficacia = $eficacia;
        $planoAcao->necessita_nova_acao = $necessitaNovaAcao;
        $planoAcao->agido_em = now();

        if ($necessitaNovaAcao) {
            $historico = $planoAcao->historico_pdca ?? [];
            $historico[] = [
                'ciclo'               => $planoAcao->ciclo_pdca,
                'evidencia_execucao'  => $planoAcao->evidencia_execucao,
                'executado_em'        => optional($planoAcao->executado_em)->toIso8601String(),
                'parecer_verificacao' => $planoAcao->parecer_verificacao,
                'verificado_em'       => optional($planoAcao->verificado_em)->toIso8601String(),
                'verificado_por'      => $planoAcao->verificado_por,
                'eficacia'            => $eficacia->value,
                'agido_em'            => now()->toIso8601String(),
            ];

            $planoAcao->historico_pdca = $historico;
            $planoAcao->ciclo_pdca++;
            $planoAcao->fase_pdca = FasePdca::PLANEJAR;
            $planoAcao->status = StatusPlanoAcao::PENDENTE;
            $planoAcao->executado_em = null;
            $planoAcao->evidencia_execucao = null;
            $planoAcao->verificado_em = null;
            $planoAcao->verificado_por = null;
            $planoAcao->parecer_verificacao = null;
            $planoAcao->agido_em = null;
            $planoAcao->eficacia = null;
        } else {
            $planoAcao->status = StatusPlanoAcao::CONCLUIDO;
            $planoAcao->concluido_em = now();
        }

        $planoAcao->save();

        return $planoAcao->fresh(['categoria', 'ghe', 'template', 'verificadoPor']);
    }

    private function registrarExecucao(PlanoAcao $planoAcao, array $dados): void
    {
        abort_if(empty($dados['evidencia_execucao']), 422, 'Informe a evidência da execução antes de avançar para Verificar.');

        $planoAcao->evidencia_execucao = $dados['evidencia_execucao'];
        $planoAcao->executado_em = now();
    }

    private function registrarVerificacao(PlanoAcao $planoAcao, array $dados, User $user): void
    {
        abort_if(empty($dados['parecer_verificacao']), 422, 'Informe o parecer de verificação antes de avançar para Agir.');

        $planoAcao->parecer_verificacao = $dados['parecer_verificacao'];
        $planoAcao->verificado_em = now();
        $planoAcao->verificado_por = $user->id;
    }
}
