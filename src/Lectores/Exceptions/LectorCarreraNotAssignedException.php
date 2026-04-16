<?php

declare(strict_types=1);

namespace App\Lectores\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class LectorCarreraNotAssignedException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('La carrera no esta asignada al lector');
    }
}
