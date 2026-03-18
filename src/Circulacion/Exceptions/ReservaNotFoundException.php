<?php

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\EntityNotFoundException;

class ReservaNotFoundException extends EntityNotFoundException
{
    public function __construct(mixed $identifier)
    {
        parent::__construct('Reserva', $identifier);
    }
}