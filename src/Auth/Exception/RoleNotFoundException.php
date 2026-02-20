<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use App\Shared\Exceptions\EntityNotFoundException;

class RoleNotFoundException extends EntityNotFoundException
{
    public function __construct(string $message = "Role not found")
    {
        parent::__construct('Role', $message);
    }
}
