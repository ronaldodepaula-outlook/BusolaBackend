<?php

namespace App\Modules\Pesquisa\Enums;

enum TipoConceito: string
{
    case ESCALA_LIKERT = 'escala_likert';
    case FREQUENCIA = 'frequencia';
    case NUMERICA = 'numerica';
    case PERSONALIZADO = 'personalizado';
}
