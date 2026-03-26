<?php

declare(strict_types=1);

namespace App\Circulacion\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class ReservaNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('Reserva no encontrada');
    }
}
