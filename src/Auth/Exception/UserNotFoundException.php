<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use App\Shared\Exceptions\EntityNotFoundException;

class UserNotFoundException extends EntityNotFoundException
{
    public function __construct(string $dni)
    {
        parent::__construct('User', $dni);
    }
}
