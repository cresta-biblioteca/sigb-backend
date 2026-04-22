<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class RenovacionNoPermitidaException extends BusinessRuleException
{
    public function __construct(string $motivo)
    {
        parent::__construct("No se puede renovar el préstamo: {$motivo}");
    }

    public function getErrorCode(): string
    {
        return 'RENOVACION_NO_PERMITIDA';
    }
}
