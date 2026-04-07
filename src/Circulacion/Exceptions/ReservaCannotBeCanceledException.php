<?php

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class ReservaCannotBeCanceledException extends BusinessRuleException
{
    public function __construct(string $message = "La reserva no puede ser cancelada")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "RESERVA_NO_PUEDE_SER_CANCELADA";
    }
}