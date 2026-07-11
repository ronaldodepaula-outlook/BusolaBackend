<?php

namespace App\Modules\Pesquisa\Contracts;

/**
 * Contrato de um motor de cálculo de risco: converte a média de resposta
 * (escala 1-5) em Probabilidade e cruza com a Severidade fixa da categoria
 * para produzir o nível de risco final. Cada Padrão de Formulário (ver
 * {@see \App\Modules\Pesquisa\Enums\ModeloCalculoRisco}) usa sua própria
 * implementação, resolvida por {@see \App\Modules\Pesquisa\Services\MotorCalculoRiscoResolver}.
 */
interface MotorCalculoRiscoInterface
{
    /** Converte a média (1-5) em Probabilidade (1-5), ou null quando abaixo do limite de materialidade (se houver). */
    public function probabilidade(float $media): ?int;

    public function classificar(?int $probabilidade, int $severidade): NivelRiscoInterface;

    /**
     * Atalho: média + severidade fixa → nível de risco final.
     *
     * @return array{probabilidade: ?int, severidade: int, nivel: NivelRiscoInterface}
     */
    public function avaliar(float $media, int $severidade): array;
}
