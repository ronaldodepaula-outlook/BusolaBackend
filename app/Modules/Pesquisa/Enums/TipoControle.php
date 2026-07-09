<?php

namespace App\Modules\Pesquisa\Enums;

enum TipoControle: string
{
    case ESTRUTURAL = 'estrutural';
    case ADMINISTRATIVO = 'administrativo';
    case PSICOSSOCIAL = 'psicossocial';

    public function label(): string
    {
        return match ($this) {
            self::ESTRUTURAL => 'Estrutural',
            self::ADMINISTRATIVO => 'Administrativo',
            self::PSICOSSOCIAL => 'Psicossocial',
        };
    }
}
