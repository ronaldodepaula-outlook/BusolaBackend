<?php

namespace App\Modules\Pesquisa\Enums;

enum Eficacia: string
{
    case EFICAZ = 'eficaz';
    case PARCIALMENTE_EFICAZ = 'parcialmente_eficaz';
    case INEFICAZ = 'ineficaz';

    public function label(): string
    {
        return match ($this) {
            self::EFICAZ => 'Eficaz',
            self::PARCIALMENTE_EFICAZ => 'Parcialmente eficaz',
            self::INEFICAZ => 'Ineficaz',
        };
    }
}
