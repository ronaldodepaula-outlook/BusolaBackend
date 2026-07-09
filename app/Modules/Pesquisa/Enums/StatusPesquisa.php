<?php

namespace App\Modules\Pesquisa\Enums;

enum StatusPesquisa: string
{
    case RASCUNHO = 'rascunho';
    case ATIVA = 'ativa';
    case ENCERRADA = 'encerrada';
    case CANCELADA = 'cancelada';
}
