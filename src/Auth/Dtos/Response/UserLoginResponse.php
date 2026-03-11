<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserLoginResponse', required: ['token'])]
readonly class UserLoginResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'Token JWT de autenticación', type: 'string')]
        private string $token
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
