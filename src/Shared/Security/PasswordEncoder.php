<?php

declare(strict_types=1);

namespace App\Shared\Security;

class PasswordEncoder
{
    private const string ALGORITHM = PASSWORD_BCRYPT;
    private const int COST = 12;

    public function hash(string $password): string
    {
        return password_hash($password, self::ALGORITHM, ['cost' => self::COST]);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::ALGORITHM, ['cost' => self::COST]);
    }
}
