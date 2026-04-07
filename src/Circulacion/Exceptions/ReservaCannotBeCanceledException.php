<?php

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class ReservaCannotBeCanceledException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct("Solo pueden cancelarse reservas en estado PENDIENTE");
    }

    public function getErrorCode(): string
    {
        return "RESERVA_NO_PUEDE_SER_CANCELADA";
    }
}