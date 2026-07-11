<?php

namespace App\Modules\Pesquisa\Services;

use App\Modules\Pesquisa\Contracts\MotorCalculoRiscoInterface;
use App\Modules\Pesquisa\Contracts\NivelRiscoInterface;
use App\Modules\Pesquisa\Enums\NivelRiscoCopsoqSimplificado;

/**
 * Motor de cálculo de risco psicossocial do padrão "COPSOQ II resumido"
 * (`ModeloCalculoRisco::COPSOQ_SIMPLIFICADO`), fonte:
 * documentos/planilha_perguntas_psicossociais.xlsx.
 *
 * Diferente de {@see RiscoCalculator} (padrão NR-1 completo), este modelo
 * calcula o risco como o produto matemático simples Probabilidade × Severidade,
 * classificado por faixas numéricas fixas — é exatamente assim que a aba
 * "Matriz Base"/"Cálculo" da planilha de referência descreve o método
 * ("Multiplicar um pelo outro GxP"). Não há piso de "exposição não
 * significativa": toda média de 1 a 5 sempre produz uma Probabilidade de 1 a 5.
 */
class RiscoCalculatorCopsoqSimplificado implements MotorCalculoRiscoInterface
{
    public function probabilidade(float $media): ?int
    {
        return match (true) {
            $media <= 1.79 => 1,
            $media <= 2.59 => 2,
            $media <= 3.39 => 3,
            $media <= 4.19 => 4,
            default => 5,
        };
    }

    public function classificar(?int $probabilidade, int $severidade): NivelRiscoInterface
    {
        $probabilidade = max(1, min(5, $probabilidade ?? 1));
        $severidade = max(1, min(5, $severidade));

        $produto = $probabilidade * $severidade;

        return match (true) {
            $produto <= 3 => NivelRiscoCopsoqSimplificado::BAIXO,
            $produto <= 8 => NivelRiscoCopsoqSimplificado::MODERADO,
            $produto <= 14 => NivelRiscoCopsoqSimplificado::ALTO,
            default => NivelRiscoCopsoqSimplificado::CRITICO,
        };
    }

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
