<?php

namespace App\Modules\Pesquisa\Enums;

enum StatusFormulario: string
{
    case RASCUNHO = 'rascunho';
    case PUBLICADO = 'publicado';
    case ARQUIVADO = 'arquivado';
}
