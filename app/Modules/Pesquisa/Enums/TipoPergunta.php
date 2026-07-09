<?php

namespace App\Modules\Pesquisa\Enums;

enum TipoPergunta: string
{
    case ESCALA = 'escala';
    case TEXTO = 'texto';
    case NUMERO = 'numero';
    case DATA = 'data';
    case SIM_NAO = 'sim_nao';
    case MULTIPLA_ESCOLHA = 'multipla_escolha';
    case UNICA_ESCOLHA = 'unica_escolha';

    /**
     * Tipos que exigem um conceito de avaliação (escala de opções) associado.
     *
     * @return TipoPergunta[]
     */
    public static function queExigemConceito(): array
    {
        return [self::ESCALA, self::MULTIPLA_ESCOLHA, self::UNICA_ESCOLHA];
    }

    public function exigeConceito(): bool
    {
        return in_array($this, self::queExigemConceito(), true);
    }
}
