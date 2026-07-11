<?php

namespace App\Modules\Pesquisa\Support;

use App\Modules\Pesquisa\Contracts\FatorRiscoReferenciaInterface;
use App\Modules\Pesquisa\Enums\CategoriaReferencia;
use App\Modules\Pesquisa\Enums\CategoriaReferenciaCopsoqSimplificado;
use ValueError;

/**
 * Ponto único que resolve uma string persistida em
 * `pesq_categorias.categoria_referencia` (ou em `resultado['categoria_referencia']`)
 * para a instância de enum correta, seja ela do padrão NR-1 completo ou do
 * padrão COPSOQ resumido — os dois conjuntos de valores nunca colidem
 * (nomes de categoria diferentes em cada padrão), então a tentativa em
 * sequência é sempre inequívoca.
 */
class FatorRiscoReferenciaResolver
{
    public static function resolver(string $valor): FatorRiscoReferenciaInterface
    {
        $referencia = CategoriaReferencia::tryFrom($valor)
            ?? CategoriaReferenciaCopsoqSimplificado::tryFrom($valor);

        if ($referencia === null) {
            throw new ValueError("\"{$valor}\" não corresponde a nenhum fator de risco de referência conhecido.");
        }

        return $referencia;
    }

    public static function tentarResolver(?string $valor): ?FatorRiscoReferenciaInterface
    {
        if ($valor === null) {
            return null;
        }

        return CategoriaReferencia::tryFrom($valor)
            ?? CategoriaReferenciaCopsoqSimplificado::tryFrom($valor);
    }

    /**
     * Todos os valores válidos dos dois padrões combinados — usado pela
     * validação de `categoria_referencia` ao criar/editar uma Categoria, já
     * que o formulário pode seguir qualquer um dos dois padrões.
     *
     * @return string[]
     */
    public static function todosOsValores(): array
    {
        return [
            ...array_map(fn ($c) => $c->value, CategoriaReferencia::cases()),
            ...array_map(fn ($c) => $c->value, CategoriaReferenciaCopsoqSimplificado::cases()),
        ];
    }
}
