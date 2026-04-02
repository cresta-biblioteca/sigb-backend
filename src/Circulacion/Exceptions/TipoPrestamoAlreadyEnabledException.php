<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\BusinessValidationException;

/**
 * Excepcion lanzada cuando se intenta habilitar un tipo de prestamo que ya esta habilitado.
 */
class TipoPrestamoAlreadyEnabledException extends BusinessValidationException
{
    public function __construct(string $field, string $message)
    {
        parent::__construct($field, $message);
    }
}
