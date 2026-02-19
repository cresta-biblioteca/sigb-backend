<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use App\Shared\Exceptions\EntityAlreadyExistsException;
class UserAlreadyExistsException extends EntityAlreadyExistsException
{
    public function __construct(string $dni)
    {
        parent::__construct('User', 'dni', $dni);
    }
}