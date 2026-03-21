<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class LectorYaTieneReservaOPrestamoException extends BusinessRuleException
{
    public function __construct(int $lectorId, int $articuloId)
    {
        parent::__construct(
            errorCode: 'LECTOR_YA_TIENE_RESERVA_O_PRESTAMO',
            safeMsg: 'Ya tenés una reserva pendiente o un préstamo activo para este artículo',
            internalMessage: "El lector {$lectorId} ya tiene una reserva pendiente o préstamo activo del artículo {$articuloId}"
        );
    }
}
