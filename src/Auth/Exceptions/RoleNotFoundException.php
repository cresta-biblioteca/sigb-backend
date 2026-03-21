<?php

declare(strict_types=1);

namespace App\Auth\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class RoleNotFoundException extends NotFoundException
{
    public function __construct(mixed $identifier)
    {
        parent::__construct('Role', $identifier);
    }
}
