<?php

declare(strict_types=1);

namespace App\Shared\Http;

class JsonHelper
{
    public static function jsonResponse($data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }
}
