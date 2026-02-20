<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Response;

use JsonSerializable;

readonly class UserRegisterResponse implements JsonSerializable
{
    public function __construct(
        private int $userId,
        private int $lectorId,
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
