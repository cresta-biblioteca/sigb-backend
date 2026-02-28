<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Request;

readonly class ChangePasswordRequest
{
    public function __construct(
        private readonly string $currentPassword,
        private readonly string $newPassword
    ) {
    }

    public function getCurrentPassword(): string
    {
        return $this->currentPassword;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }
}
