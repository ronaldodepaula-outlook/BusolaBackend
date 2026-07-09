<?php

namespace App\Modules\Pesquisa\Enums;

enum StatusPlanoAcao: string
{
    case PENDENTE = 'pendente';
    case EM_ANDAMENTO = 'em_andamento';
    case CONCLUIDO = 'concluido';
    case ATRASADO = 'atrasado';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::EM_ANDAMENTO => 'Em andamento',
            self::CONCLUIDO => 'Concluído',
            self::ATRASADO => 'Atrasado',
        };
    }
}
