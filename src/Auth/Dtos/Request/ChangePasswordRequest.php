<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Request;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ChangePasswordRequest', required: ['current_password', 'new_password'])]
readonly class ChangePasswordRequest
{
    public function __construct(
        #[OA\Property(property: 'current_password', type: 'string', format: 'password')]
        private readonly string $currentPassword,
        #[OA\Property(
            property: 'new_password',
            description: 'Mínimo 8 caracteres, una mayúscula, una minúscula y un número',
            type: 'string',
            format: 'password'
        )]
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
