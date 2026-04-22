<?php

declare(strict_types=1);

namespace App\Lectores\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class LectorCarreraAlreadyAssignedException extends AlreadyExistsException
{
    public function __construct()
    {
        parent::__construct('La carrera ya esta asignada al lector');
    }
}
