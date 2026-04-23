<?php

declare(strict_types=1);

namespace App\Shared\Security;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtTokenProvider
{
    private const int EXPIRATION_TIME = 3600; // 1 hour
    private const string ALGORITHM = 'HS256';
    private string $secretKey;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $key = $_ENV['JWT_SECRET'] ?? false;
        if (!$key || strlen($key) < 32) {
            throw new Exception('JWT_SECRET must be set and at least 32 characters long');
        }
        $this->secretKey = $key;
    }

    /**
     * @throws Exception
     */
    public function validateToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secretKey, self::ALGORITHM));
    }

    public function generateToken(int $userId, string $role, string $dni, ?int $lectorId = null): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + self::EXPIRATION_TIME;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $userId,
            'role' => $role,
            'dni' => $dni,
            'lector_id' => $lectorId,
        ];

        return JWT::encode($payload, $this->secretKey, self::ALGORITHM);
    }
}
