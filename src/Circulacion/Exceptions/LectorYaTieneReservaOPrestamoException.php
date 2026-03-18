<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use RuntimeException;

class LectorYaTieneReservaOPrestamoException extends RuntimeException
{
    public function __construct(int $lectorId, int $articuloId)
    {
        parent::__construct(
            "El lector {$lectorId} ya tiene una reserva pendiente o un préstamo activo del artículo {$articuloId}"
        );
    }
}
