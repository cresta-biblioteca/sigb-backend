<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\Shared\Exceptions\ForbiddenException;

class OwnershipGuard
{
    public static function assertLector(callable $getLectorId): void
    {
        if ($_SERVER['USER_ROLE'] !== 'lector') {
            return;
        }

        if ((int) ($_SERVER['USER_LECTOR_ID'] ?? 0) !== $getLectorId()) {
            throw new ForbiddenException();
        }
    }
}
