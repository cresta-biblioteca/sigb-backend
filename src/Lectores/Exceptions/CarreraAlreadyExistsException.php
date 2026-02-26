<?php

declare(strict_types=1);

namespace App\Lectores\Exceptions;

use App\Shared\Exceptions\EntityAlreadyExistsException;

class CarreraAlreadyExistsException extends EntityAlreadyExistsException
{
    public function __construct(string $field, mixed $value)
    {
        parent::__construct("Carrera", $field, $value);
    }
}
