<?php

namespace App\Modules\Pesquisa\Services;

use App\Modules\Pesquisa\Enums\NivelRisco;

/**
 * Motor de cálculo de risco psicossocial: converte a média COPSOQ II (1 a 5)
 * em Probabilidade por faixa fixa, e cruza Probabilidade × Severidade na
 * matriz de risco corporativa — reproduzindo exatamente as Seções 3.5 a 3.8
 * do relatório técnico modelo e as abas PROBABILIDADE/MATRIZ da planilha de
 * referência. O resultado NÃO é o produto matemático das duas variáveis: é
 * uma tabela de consulta, como o próprio documento de referência ressalta.
 */
class RiscoCalculator
{
    /** Abaixo desta média, a categoria é considerada sem exposição significativa e fica fora da matriz. */
    private const LIMITE_MATERIALIDADE = 1.30;

    /**
     * Matriz de risco corporativa (linha = Probabilidade 1-5, coluna = Severidade 1-5).
     *
     * @var array<int, array<int, NivelRisco>>
     */
    private const MATRIZ = [
        1 => [1 => NivelRisco::TRIVIAL, 2 => NivelRisco::TRIVIAL, 3 => NivelRisco::TOLERAVEL, 4 => NivelRisco::MODERADO, 5 => NivelRisco::MODERADO],
        2 => [1 => NivelRisco::TRIVIAL, 2 => NivelRisco::TOLERAVEL, 3 => NivelRisco::MODERADO, 4 => NivelRisco::MODERADO, 5 => NivelRisco::SUBSTANCIAL],
        3 => [1 => NivelRisco::TRIVIAL, 2 => NivelRisco::TOLERAVEL, 3 => NivelRisco::MODERADO, 4 => NivelRisco::SUBSTANCIAL, 5 => NivelRisco::INTOLERAVEL],
        4 => [1 => NivelRisco::TOLERAVEL, 2 => NivelRisco::TOLERAVEL, 3 => NivelRisco::MODERADO, 4 => NivelRisco::SUBSTANCIAL, 5 => NivelRisco::INTOLERAVEL],
        5 => [1 => NivelRisco::TOLERAVEL, 2 => NivelRisco::MODERADO, 3 => NivelRisco::SUBSTANCIAL, 4 => NivelRisco::INTOLERAVEL, 5 => NivelRisco::INTOLERAVEL],
    ];

    /**
     * Converte a média COPSOQ II (escala 1-5) em Probabilidade (1-5), ou null
     * quando a média está abaixo do limite de materialidade da exposição.
     */
    public function probabilidade(float $media): ?int
    {
        if ($media < self::LIMITE_MATERIALIDADE) {
            return null;
        }

        return match (true) {
            $media <= 1.49 => 1,
            $media <= 2.49 => 2,
            $media <= 3.49 => 3,
            $media <= 4.29 => 4,
            default => 5,
        };
    }

    /**
     * Classifica o risco a partir da Probabilidade (já convertida) e da
     * Severidade fixa da categoria. Probabilidade null (< limite de
     * materialidade) sempre resulta em NAO_SIGNIFICATIVO, independente da severidade.
     */
    public function classificar(?int $probabilidade, int $severidade): NivelRisco
    {
        if ($probabilidade === null) {
            return NivelRisco::NAO_SIGNIFICATIVO;
        }

        $probabilidade = max(1, min(5, $probabilidade));
        $severidade = max(1, min(5, $severidade));

        return self::MATRIZ[$probabilidade][$severidade];
    }

    /** Atalho: média COPSOQ + severidade fixa → nível de risco final. */
    public function avaliar(float $media, int $severidade): array
    {
        $probabilidade = $this->probabilidade($media);

        return [
            'probabilidade' => $probabilidade,
            'severidade'    => $severidade,
            'nivel'         => $this->classificar($probabilidade, $severidade),
        ];
    }
}
