<?php

declare(strict_types=1);

namespace App\Auth\Exceptions;

use App\Shared\Exceptions\AlreadyExistsException;

class UserAlreadyExistsException extends AlreadyExistsException
{
    public function __construct()
    {
        parent::__construct('El usuario ya existe');
    }
}
