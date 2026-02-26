<?php

declare(strict_types=1);

namespace App\Lectores\Exceptions;

use App\Shared\Exceptions\EntityNotFoundException;

class CarreraNotFoundException extends EntityNotFoundException
{
    public function __construct(mixed $identifier)
    {
        parent::__construct("Carrera", $identifier);
    }
}
