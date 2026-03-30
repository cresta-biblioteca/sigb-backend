<?php

declare(strict_types=1);

namespace App\Circulacion\Models;

enum EstadoReserva: string
{
    case PENDIENTE = 'PENDIENTE';
    case COMPLETADA = 'COMPLETADA';
    case CANCELADA = 'CANCELADA';
    case VENCIDA = 'VENCIDA';
}
