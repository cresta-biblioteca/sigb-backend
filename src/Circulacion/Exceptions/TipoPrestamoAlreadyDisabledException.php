<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class TipoPrestamoAlreadyDisabledException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct("EL tipo de prestamo ya se encuentra deshabilitado");
    }

    public function getErrorCode(): string
    {
        return "TIPO_PRESTAMO_YA_DESHABILITADO";
    }
}
