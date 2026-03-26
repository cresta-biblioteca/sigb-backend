<?php

declare(strict_types=1);

namespace App\Auth\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class UserNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('Usuario no encontrado');
    }
}
