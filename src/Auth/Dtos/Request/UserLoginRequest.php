<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Request;

readonly class UserLoginRequest
{
    public function __construct(
        public string $dni,
        public string $password
    ) {
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
