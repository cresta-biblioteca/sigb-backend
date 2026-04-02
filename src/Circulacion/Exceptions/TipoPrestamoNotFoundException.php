<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\EntityNotFoundException;

class TipoPrestamoNotFoundException extends EntityNotFoundException
{
    public function __construct(mixed $identifier)
    {
        parent::__construct("TipoPrestamo", $identifier);
    }
}
