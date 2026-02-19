<?php

declare(strict_types=1);

namespace App\Auth\Dtos\Response;

readonly class UserRegisterResponse
{
    public int $userId;
    public int $lectorId;
    public string $fullName;

    public function __construct(
        int $userId,
        int $lectorId,
        string $fullName
    ) {
        $this->userId = $userId;
        $this->lectorId = $lectorId;
        $this->fullName = $fullName;
    }
}
