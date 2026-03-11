<?php

declare(strict_types=1);

namespace App\Shared\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'SIGB API',
    description: 'API REST del Sistema de Gestión Bibliotecaria (SIGB)',
)]
#[OA\Server(
    url: '/api/v1',
    description: 'Servidor de desarrollo'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token JWT obtenido en el endpoint de login'
)]
class OpenApiConfig
{
}
