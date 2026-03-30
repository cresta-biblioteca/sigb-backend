<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class LectorYaTieneReservaOPrestamoException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Ya tenés una reserva pendiente o un préstamo activo para este artículo');
    }

    public function getErrorCode(): string
    {
        return 'LECTOR_YA_TIENE_RESERVA_O_PRESTAMO';
    }
}
