<?php

namespace App\Modules\Pesquisa\Enums;

/**
 * Motor de cálculo de risco associado a um Padrão de Formulário — define
 * quais categorias/severidades, faixas de probabilidade e matriz de
 * classificação se aplicam às campanhas que usam aquele padrão. Resolvido
 * para a implementação concreta por {@see \App\Modules\Pesquisa\Services\MotorCalculoRiscoResolver}.
 */
enum ModeloCalculoRisco: string
{
    /** 11 categorias NR-1/COPSOQ II, matriz assimétrica de 6 níveis — modelo original do sistema. */
    case NR1_COMPLETO = 'nr1_completo';

    /** 7 dimensões COPSOQ II resumido, matriz P×S de 4 níveis — planilha_perguntas_psicossociais.xlsx. */
    case COPSOQ_SIMPLIFICADO = 'copsoq_simplificado';

    public function label(): string
    {
        return match ($this) {
            self::NR1_COMPLETO => 'NR-1 / COPSOQ II completo (11 categorias)',
            self::COPSOQ_SIMPLIFICADO => 'COPSOQ II resumido (7 dimensões)',
        };
    }
}
