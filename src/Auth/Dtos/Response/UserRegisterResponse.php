<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Response;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserRegisterResponse', required: ['userId', 'lectorId', 'fullName'])]
readonly class UserRegisterResponse implements JsonSerializable
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        private int $userId,
        #[OA\Property(type: 'integer', example: 1)]
        private int $lectorId,
        #[OA\Property(type: 'string', example: 'Juan Pérez')]
        private string $fullName
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'userId' => $this->userId,
            'lectorId' => $this->lectorId,
            'fullName' => $this->fullName,
        ];
    }
}
