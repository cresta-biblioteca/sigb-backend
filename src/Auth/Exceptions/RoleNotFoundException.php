<?php

declare(strict_types=1);

namespace App\Auth\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class RoleNotFoundException extends NotFoundException
{
    public function __construct()
    {
        parent::__construct('Rol no encontrado');
    }
}
