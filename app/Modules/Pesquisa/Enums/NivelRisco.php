<?php

namespace App\Modules\Pesquisa\Enums;

use App\Modules\Pesquisa\Contracts\NivelRiscoInterface;

/**
 * Classificação final de risco (Seção 3.8 do relatório técnico modelo),
 * resultado do cruzamento Probabilidade × Severidade na matriz de risco
 * corporativa (RiscoCalculator::classificar()). Este é o padrão "NR-1
 * completo" — ver também {@see NivelRiscoCopsoqSimplificado} para o padrão
 * COPSOQ II resumido.
 */
enum NivelRisco: string implements NivelRiscoInterface
{
    case NAO_SIGNIFICATIVO = 'nao_significativo';
    case TRIVIAL = 'trivial';
    case TOLERAVEL = 'toleravel';
    case MODERADO = 'moderado';
    case SUBSTANCIAL = 'substancial';
    case INTOLERAVEL = 'intoleravel';

    public function label(): string
    {
        return match ($this) {
            self::NAO_SIGNIFICATIVO => 'Exposição Não Significativa',
            self::TRIVIAL => 'Trivial',
            self::TOLERAVEL => 'Tolerável',
            self::MODERADO => 'Moderado / De Atenção',
            self::SUBSTANCIAL => 'Substancial / Crítico',
            self::INTOLERAVEL => 'Intolerável / Não Tolerável',
        };
    }

    public function farolEmoji(): string
    {
        return match ($this) {
            self::NAO_SIGNIFICATIVO => '⚪',
            self::TRIVIAL => '🔵',
            self::TOLERAVEL => '🟢',
            self::MODERADO => '🟡',
            self::SUBSTANCIAL => '🟠',
            self::INTOLERAVEL => '🔴',
        };
    }

    public function farolCor(): string
    {
        return match ($this) {
            self::NAO_SIGNIFICATIVO => '#9AA3AC',
            self::TRIVIAL => '#2F6BB8',
            self::TOLERAVEL => '#3E8E4F',
            self::MODERADO => '#C9A227',
            self::SUBSTANCIAL => '#D97F2B',
            self::INTOLERAVEL => '#C23B2C',
        };
    }

    /** Diretrizes de gerenciamento — Seção 3.8 do relatório técnico modelo. */
    public function diretriz(): string
    {
        return match ($this) {
            self::NAO_SIGNIFICATIVO => 'Não há evidências quantitativas de exposição relevante ao fator psicossocial avaliado. O fator permanece registrado para fins de monitoramento periódico, sem necessidade de inclusão na matriz de risco ou de plano de ação específico, salvo quando existirem evidências qualitativas, eventos críticos ou indicadores organizacionais que justifiquem tratamento diferenciado.',
            self::TRIVIAL => 'Representa condição de risco muito baixo, sem evidências de exposição psicossocial relevante capaz de produzir impactos significativos à saúde mental, à funcionalidade ocupacional ou ao ambiente organizacional. Não há necessidade de medidas adicionais além da manutenção dos controles existentes e do monitoramento periódico.',
            self::TOLERAVEL => 'Representa condição de baixa criticidade, com exposição psicossocial limitada ou adequadamente controlada. Recomenda-se manutenção das medidas preventivas existentes, fortalecimento das boas práticas organizacionais e monitoramento contínuo para prevenir agravamentos futuros.',
            self::MODERADO => 'Representa condição que requer atenção e monitoramento ativo, pois há fatores psicossociais com potencial de repercussão moderada sobre a saúde dos trabalhadores e sobre o desempenho organizacional. Recomenda-se elaboração de ações preventivas e corretivas proporcionais ao contexto.',
            self::SUBSTANCIAL => 'Representa condição de risco relevante, com potencial significativo de repercussão sobre a saúde mental dos trabalhadores, o clima organizacional e a produtividade. Exige elaboração e implementação prioritária de plano de ação estruturado, com definição de responsáveis, prazos e mecanismos de acompanhamento.',
            self::INTOLERAVEL => 'Representa condição de risco inaceitável, com elevado potencial de dano à saúde mental, à funcionalidade ocupacional e à estabilidade organizacional. Exige intervenção imediata e prioritária, com implementação urgente de medidas de eliminação, redução ou controle da exposição aos fatores psicossociais identificados.',
        };
    }

    /** Rótulo curto usado nas tabelas de resultado/plano de ação do relatório técnico (coluna "Nível"). */
    public function rotuloRelatorio(): string
    {
        return match ($this) {
            self::NAO_SIGNIFICATIVO => 'Monitoramento',
            self::TRIVIAL, self::TOLERAVEL => 'Irrelevante',
            self::MODERADO => 'De Atenção',
            self::SUBSTANCIAL => 'Crítico',
            self::INTOLERAVEL => 'Não Tolerável',
        };
    }

    /** Faixa correspondente na biblioteca de templates de Plano de Ação, ou null quando não se aplica. */
    public function nivelBaseAcao(): ?NivelBaseAcao
    {
        return match ($this) {
            self::NAO_SIGNIFICATIVO => null,
            self::TRIVIAL => NivelBaseAcao::IRRELEVANTE,
            self::TOLERAVEL => NivelBaseAcao::BAIXO,
            self::MODERADO => NivelBaseAcao::MEDIO,
            self::SUBSTANCIAL => NivelBaseAcao::ALTO,
            self::INTOLERAVEL => NivelBaseAcao::CRITICO,
        };
    }
}
