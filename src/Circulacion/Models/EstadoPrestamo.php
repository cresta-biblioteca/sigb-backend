<?php

declare(strict_types=1);

namespace App\Circulacion\Models;

enum EstadoPrestamo: string
{
    case VIGENTE = 'VIGENTE';
    case COMPLETADO_EXITO = 'COMPLETADO_EXITO';
    case COMPLETADO_VENCIDO = 'COMPLETADO_VENCIDO';
    case INCONVENIENTE = 'INCONVENIENTE';
}
