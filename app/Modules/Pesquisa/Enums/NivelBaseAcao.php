<?php

namespace App\Modules\Pesquisa\Enums;

/**
 * As 5 faixas usadas na biblioteca de templates de Plano de Ação (aba
 * BASE_ACAO da planilha de referência). Cada faixa só muda o prefixo da ação,
 * o "como executar" e o prazo — a ação-base em si é fixa por
 * categoria/tipo de controle (ver PlanoAcaoTemplate).
 */
enum NivelBaseAcao: string
{
    case CRITICO = 'Risco Crítico';
    case ALTO = 'Risco Alto';
    case MEDIO = 'Risco Médio';
    case BAIXO = 'Risco Baixo';
    case IRRELEVANTE = 'Risco Irrelevante';

    public const EVIDENCIA_PADRAO = 'Políticas, atas, relatórios, fotos, listas';
    public const RESPONSAVEL_PADRAO = 'RH / Liderança / SESMT / Diretoria';

    public function prefixoAcao(): string
    {
        return match ($this) {
            self::CRITICO => 'Intervenção imediata para',
            self::ALTO => 'Executar plano prioritário para',
            self::MEDIO => 'Realizar ajustes progressivos para',
            self::BAIXO => 'Monitorar e sustentar controles sobre',
            self::IRRELEVANTE => 'Manter controles existentes relacionados a',
        };
    }

    public function comoExecutarPadrao(): string
    {
        return $this === self::IRRELEVANTE
            ? 'Manter monitoramento periódico e controles preventivos.'
            : 'Executar diagnóstico, definir plano, responsáveis e indicadores de acompanhamento.';
    }

    public function prazoPadrao(): string
    {
        return match ($this) {
            self::CRITICO => '6 meses',
            self::ALTO => '6–9 meses',
            self::MEDIO => '9–12 meses',
            self::BAIXO => '12 meses',
            self::IRRELEVANTE => 'Contínuo',
        };
    }
}
