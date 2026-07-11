<?php

namespace App\Modules\Pesquisa\Contracts;

/**
 * Contrato comum a todo enum que representa um "fator de risco psicossocial
 * de referência" — hoje implementado por {@see \App\Modules\Pesquisa\Enums\CategoriaReferencia}
 * (padrão NR-1/COPSOQ II completo, 11 categorias) e por
 * {@see \App\Modules\Pesquisa\Enums\CategoriaReferenciaCopsoqSimplificado}
 * (padrão COPSOQ II resumido, 7 dimensões).
 *
 * Permite que `Categoria::categoria_referencia` (ver ReferenciaFatorRiscoCast)
 * e todo o código que consome essa propriedade (RelatorioTecnicoService,
 * CategoriaService, o relatório técnico em PDF) funcionem de forma
 * polimórfica, sem saber qual dos dois padrões está em uso.
 */
interface FatorRiscoReferenciaInterface
{
    public function label(): string;

    /** Severidade (S) fixa oficial deste fator — nunca calculada a partir de respostas. */
    public function severidadePadrao(): int;

    /** Anexo I do relatório técnico — descrição técnica do fator de risco. */
    public function descricaoTecnica(): string;

    /**
     * Anexo I do relatório técnico — possíveis doenças relacionadas (CID),
     * quando o padrão fornecer essa referência. Pode retornar array vazio.
     *
     * @return string[]
     */
    public function doencasRelacionadas(): array;
}
