<?php

namespace App\Modules\Pesquisa\Contracts;

use App\Modules\Pesquisa\Enums\NivelBaseAcao;

/**
 * Contrato comum a todo enum de classificação final de risco — hoje
 * implementado por {@see \App\Modules\Pesquisa\Enums\NivelRisco} (padrão
 * NR-1/COPSOQ II completo, 6 níveis) e por
 * {@see \App\Modules\Pesquisa\Enums\NivelRiscoCopsoqSimplificado} (padrão
 * COPSOQ II resumido, 4 níveis).
 */
interface NivelRiscoInterface
{
    public function label(): string;

    public function farolEmoji(): string;

    public function farolCor(): string;

    /** Rótulo curto usado nas tabelas de resultado/plano de ação do relatório técnico. */
    public function rotuloRelatorio(): string;

    /** Diretrizes de gerenciamento associadas a este nível. */
    public function diretriz(): string;

    /**
     * Faixa correspondente na biblioteca de templates de Plano de Ação, ou
     * null quando não se aplica (ex.: o padrão COPSOQ resumido não possui
     * catálogo de ações próprio — a planilha de referência dele não define
     * um equivalente à aba BASE_ACAO do padrão completo).
     */
    public function nivelBaseAcao(): ?NivelBaseAcao;
}
