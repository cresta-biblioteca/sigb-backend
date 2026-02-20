<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Response;

readonly class UserLoginResponse
{
    public function __construct(private string $token)
    {
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
