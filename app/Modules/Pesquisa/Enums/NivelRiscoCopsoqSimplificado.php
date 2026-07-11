<?php

namespace App\Modules\Pesquisa\Enums;

use App\Modules\Pesquisa\Contracts\NivelRiscoInterface;

/**
 * Classificação final de risco do padrão "COPSOQ II resumido", fonte:
 * documentos/planilha_perguntas_psicossociais.xlsx (aba "Matriz Base" /
 * "Cálculo", tabela "Resultado | Classificação | Farol"). 4 níveis, versus os
 * 6 do padrão NR-1 completo ({@see NivelRisco}).
 *
 * Nota de coerência: a grade colorida da própria planilha (P×S → emoji)
 * apresenta pequenas inconsistências com a tabela de faixas que ela mesma
 * declara (ex.: a célula P=1×S=5 aparece como 🟠 mas o produto 5 cai na
 * faixa "1–3 Baixo" pela tabela de faixas). {@see RiscoCalculatorCopsoqSimplificado}
 * usa a tabela de faixas numéricas (inequívoca) como fonte da verdade, não a
 * grade colorida.
 */
enum NivelRiscoCopsoqSimplificado: string implements NivelRiscoInterface
{
    case BAIXO = 'baixo';
    case MODERADO = 'moderado';
    case ALTO = 'alto';
    case CRITICO = 'critico';

    public function label(): string
    {
        return match ($this) {
            self::BAIXO => 'Baixo',
            self::MODERADO => 'Moderado',
            self::ALTO => 'Alto',
            self::CRITICO => 'Crítico',
        };
    }

    public function farolEmoji(): string
    {
        return match ($this) {
            self::BAIXO => '🟢',
            self::MODERADO => '🟡',
            self::ALTO => '🟠',
            self::CRITICO => '🔴',
        };
    }

    public function farolCor(): string
    {
        return match ($this) {
            self::BAIXO => '#3E8E4F',
            self::MODERADO => '#C9A227',
            self::ALTO => '#D97F2B',
            self::CRITICO => '#C23B2C',
        };
    }

    public function rotuloRelatorio(): string
    {
        return $this->label();
    }

    public function diretriz(): string
    {
        return match ($this) {
            self::BAIXO => 'Representa condição de baixa criticidade. Recomenda-se manutenção das medidas preventivas existentes e monitoramento periódico da dimensão avaliada.',
            self::MODERADO => 'Representa condição que requer atenção. Recomenda-se elaboração de ações preventivas proporcionais ao contexto e acompanhamento mais próximo dos indicadores desta dimensão.',
            self::ALTO => 'Representa condição de risco relevante, com potencial de repercussão sobre a saúde mental dos trabalhadores e o clima organizacional. Recomenda-se plano de ação estruturado, com responsáveis e prazos definidos.',
            self::CRITICO => 'Representa condição de risco elevado, com potencial significativo de dano à saúde mental e à estabilidade organizacional. Recomenda-se intervenção prioritária e imediata sobre a dimensão avaliada.',
        };
    }

    /** Este padrão não possui catálogo de Plano de Ação próprio — ver nota na classe. */
    public function nivelBaseAcao(): ?NivelBaseAcao
    {
        return null;
    }
}
