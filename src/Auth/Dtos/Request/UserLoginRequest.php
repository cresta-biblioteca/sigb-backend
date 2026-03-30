<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserLoginRequest', required: ['dni', 'password'])]
readonly class UserLoginRequest
{
    public function __construct(
        #[OA\Property(description: 'DNI del usuario (7-8 dígitos)', type: 'string', example: '12345678')]
        private readonly string $dni,
        #[OA\Property(type: 'string', format: 'password', example: 'Password123')]
        private readonly string $password
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
