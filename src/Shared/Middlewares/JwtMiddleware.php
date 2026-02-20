<?php

declare(strict_types=1);

namespace App\Shared\Middlewares;

use App\Shared\Security\JwtTokenProvider;
use Exception;

class JwtMiddleware
{
    public function __construct(private readonly JwtTokenProvider $jwtTokenProvider)
    {
    }

    public function handle(): bool
    {
        try {
            $token = $this->getTokenFromHeader();

            if (!$token) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized.']);
                return false;
            }

            $decodedJwt = $this->jwtTokenProvider->validateToken($token);
            $_SERVER['USER_ID'] = $decodedJwt->sub;
            $_SERVER['USER_ROLE'] = $decodedJwt->role;

            return true;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token.']);
            return false;
        }
    }

    private function getTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $parts = explode(' ', $headers['Authorization']);
            if (count($parts) === 2 && strtolower($parts[0]) === 'bearer') {
                return $parts[1];
            }
        }
        return null;
    }
}
