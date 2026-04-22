<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessRuleException;

class TipoPrestamoDeshabilitadoException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('El tipo de préstamo seleccionado no está habilitado');
    }

    public function getErrorCode(): string
    {
        return 'TIPO_PRESTAMO_DESHABILITADO';
    }
}
