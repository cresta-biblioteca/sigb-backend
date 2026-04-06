<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class TipoPrestamoAlreadyExistsException extends AlreadyExistsException
{
    public function __construct()
    {
        parent::__construct("El tipo de prestamo ya existe");
    }
}
