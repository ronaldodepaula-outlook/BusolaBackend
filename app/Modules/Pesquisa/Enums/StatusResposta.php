<?php

namespace App\Modules\Pesquisa\Enums;

enum StatusResposta: string
{
    case EM_ANDAMENTO = 'em_andamento';
    case CONCLUIDA = 'concluida';
}
