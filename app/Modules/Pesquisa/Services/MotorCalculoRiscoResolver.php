<?php

namespace App\Modules\Pesquisa\Services;

use App\Modules\Pesquisa\Contracts\FatorRiscoReferenciaInterface;
use App\Modules\Pesquisa\Contracts\MotorCalculoRiscoInterface;
use App\Modules\Pesquisa\Contracts\NivelRiscoInterface;
use App\Modules\Pesquisa\Enums\CategoriaReferencia;
use App\Modules\Pesquisa\Enums\CategoriaReferenciaCopsoqSimplificado;
use App\Modules\Pesquisa\Enums\ModeloCalculoRisco;
use App\Modules\Pesquisa\Enums\NivelRisco;
use App\Modules\Pesquisa\Enums\NivelRiscoCopsoqSimplificado;
use App\Modules\Pesquisa\Models\PadraoFormulario;

/**
 * Resolve, a partir do Padrão de Formulário de uma campanha, qual motor de
 * cálculo de risco (e quais catálogos completos de categorias/níveis) se
 * aplicam. Ponto único de decisão consumido por ResultadoService (cálculo em
 * si) e RelatorioTecnicoService (Seções 3.7/3.8 do relatório, que listam
 * TODAS as categorias/níveis do padrão, não só os efetivamente usados na
 * campanha).
 *
 * Ausência de padrão (`null`, campanhas anteriores a este recurso) sempre
 * resolve para o padrão NR-1 completo — o comportamento original do sistema.
 */
class MotorCalculoRiscoResolver
{
    public function __construct(
        private readonly RiscoCalculator $nr1Completo,
        private readonly RiscoCalculatorCopsoqSimplificado $copsoqSimplificado,
    ) {
    }

    public function resolver(?PadraoFormulario $padrao): MotorCalculoRiscoInterface
    {
        return match ($padrao?->modelo_calculo) {
            ModeloCalculoRisco::COPSOQ_SIMPLIFICADO => $this->copsoqSimplificado,
            default => $this->nr1Completo,
        };
    }

    /** @return FatorRiscoReferenciaInterface[] */
    public function todasCategorias(?PadraoFormulario $padrao): array
    {
        return match ($padrao?->modelo_calculo) {
            ModeloCalculoRisco::COPSOQ_SIMPLIFICADO => CategoriaReferenciaCopsoqSimplificado::cases(),
            default => CategoriaReferencia::cases(),
        };
    }

    /** @return NivelRiscoInterface[] */
    public function todosNiveis(?PadraoFormulario $padrao): array
    {
        return match ($padrao?->modelo_calculo) {
            ModeloCalculoRisco::COPSOQ_SIMPLIFICADO => NivelRiscoCopsoqSimplificado::cases(),
            default => NivelRisco::cases(),
        };
    }
}
