<?php

namespace App\Shared\Logger;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerFactory
{
    public static function create(string $channelName = 'API'): Logger
    {
        $logger = new Logger($channelName);

        // En entornos de contenedores, NUNCA escribas en archivos locales (.log).
        // Emite a la salida estándar de errores (stderr) para que el recolector de logs lo atrape.
        $handler = new StreamHandler('php://stderr', Logger::DEBUG);

        // CLAVE PARA LA OBSERVABILIDAD: Usar formato JSON.
        // Esto permite que herramientas como Loki o Datadog indexen los campos automáticamente.
        $handler->setFormatter(new JsonFormatter());

        $logger->pushHandler($handler);

        return $logger;
    }
}
