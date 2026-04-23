<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum TipoArticulo: string
{
    case LIBRO     = 'libro';
    case REVISTA   = 'revista';
    case TESIS     = 'tesis';
    case MAPA      = 'mapa';
    case PARTITURA = 'partitura';
}
