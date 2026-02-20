<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Response;

use JsonSerializable;

readonly class UserLoginResponse implements JsonSerializable
{
    public function __construct(private string $token)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
