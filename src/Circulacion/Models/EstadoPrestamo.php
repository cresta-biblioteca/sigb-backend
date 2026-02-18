<?php

declare(strict_types=1);

namespace App\Circulacion\Models;

enum EstadoPrestamo: string
{
    case ACTIVO = 'ACTIVO';
    case DEVUELTO = 'DEVUELTO';
    case VENCIDO = 'VENCIDO';
    case RENOVADO = 'RENOVADO';
}
