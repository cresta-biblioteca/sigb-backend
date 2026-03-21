<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class EjemplarNotFoundException extends NotFoundException
{
    public function __construct(mixed $identifier)
    {
        parent::__construct('Ejemplar', $identifier);
    }
}
