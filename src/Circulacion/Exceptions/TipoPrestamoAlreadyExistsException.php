<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\EntityAlreadyExistsException;

class TipoPrestamoAlreadyExistsException extends EntityAlreadyExistsException
{
    public function __construct(string $field, mixed $value)
    {
        parent::__construct("TipoPrestamo", $field, $value);
    }
}
