<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Modules\Pesquisa\Enums\NivelRisco;
use App\Modules\Pesquisa\Services\RiscoCalculator;
use Tests\TestCase;

/**
 * Valida o motor de cálculo de risco (Probabilidade × Severidade → Matriz)
 * contra os valores exatos do relatório técnico modelo e da planilha de
 * referência da metodologia COPSOQ II / NR-1 — não apenas contra a lógica
 * interna, para garantir que a implementação reproduz a metodologia real.
 */
class RiscoCalculatorTest extends TestCase
{
    private RiscoCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new RiscoCalculator();
    }

    public function test_probabilidade_segue_as_faixas_oficiais(): void
    {
        $this->assertNull($this->calc->probabilidade(1.10));
        $this->assertSame(1, $this->calc->probabilidade(1.30));
        $this->assertSame(1, $this->calc->probabilidade(1.49));
        $this->assertSame(2, $this->calc->probabilidade(1.50));
        $this->assertSame(2, $this->calc->probabilidade(2.49));
        $this->assertSame(3, $this->calc->probabilidade(2.50));
        $this->assertSame(3, $this->calc->probabilidade(3.49));
        $this->assertSame(4, $this->calc->probabilidade(3.50));
        $this->assertSame(4, $this->calc->probabilidade(4.29));
        $this->assertSame(5, $this->calc->probabilidade(4.30));
        $this->assertSame(5, $this->calc->probabilidade(5.00));
    }

    public function test_media_abaixo_do_limite_de_materialidade_e_nao_significativa_independente_da_severidade(): void
    {
        $nivel = $this->calc->classificar(null, 5);

        $this->assertSame(NivelRisco::NAO_SIGNIFICATIVO, $nivel);
    }

    /**
     * @dataProvider casosDoRelatorioModelo
     */
    public function test_matriz_reproduz_exemplos_reais_do_relatorio_tecnico_modelo(float $media, int $severidade, NivelRisco $esperado): void
    {
        $avaliacao = $this->calc->avaliar($media, $severidade);

        $this->assertSame($esperado, $avaliacao['nivel'], "media={$media} severidade={$severidade}");
    }

    public static function casosDoRelatorioModelo(): array
    {
        // Extraídos da Seção 5 (GHE 01/02) do relatório técnico modelo.
        return [
            'Gestão Organizacional GHE01 (P2xS3)'  => [2.22, 3, NivelRisco::MODERADO],
            'Contexto GHE01 (P3xS3)'               => [2.61, 3, NivelRisco::MODERADO],
            'Condições ambiente GHE01 (P3xS2)'     => [2.59, 2, NivelRisco::TOLERAVEL],
            'Jornada GHE01 (P3xS4)'                => [3.16, 4, NivelRisco::SUBSTANCIAL],
            'Violência GHE01 (P2xS5)'              => [2.00, 5, NivelRisco::SUBSTANCIAL],
            'Risco de morte GHE01 (P1xS5)'         => [1.34, 5, NivelRisco::MODERADO],
            'Interação pessoa-tarefa GHE02 (P2xS2)' => [1.50, 2, NivelRisco::TOLERAVEL],
        ];
    }
}
