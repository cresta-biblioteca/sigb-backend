<?php

declare(strict_types=1);

namespace App\Shared\Middlewares;

class CorsMiddleware
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;

    public function __construct()
    {
        $this->allowedOrigins = $this->parseEnvList($_ENV['CORS_ALLOWED_ORIGINS'] ?? '*');
        $this->allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        $this->allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'];
    }

    public function handle(): void
    {
        $this->setOriginHeader();

        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));
        header('Access-Control-Max-Age: 86400');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    }

    private function setOriginHeader(): void
    {
        if (in_array('*', $this->allowedOrigins)) {
            header('Access-Control-Allow-Origin: *');
            return;
        }

        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($requestOrigin && in_array($requestOrigin, $this->allowedOrigins)) {
            header("Access-Control-Allow-Origin: $requestOrigin");
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
        }
    }

    private function parseEnvList(string $value): array
    {
        if ($value === '*') {
            return ['*'];
        }

        return array_map('trim', explode(',', $value));
    }
}
