<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class ReservaNoCompletableException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('La reserva no está en estado pendiente o ya venció');
    }

    public function getErrorCode(): string
    {
        return 'RESERVA_NO_COMPLETABLE';
    }
}
