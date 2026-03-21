<?php

declare(strict_types=1);

namespace App\Lectores\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class CarreraAlreadyExistsException extends AlreadyExistsException
{
    public function __construct(string $field, mixed $value)
    {
        parent::__construct("Carrera", $field, $value);
    }
}
