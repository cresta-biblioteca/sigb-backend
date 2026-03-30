<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Exceptions\AppException;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Logger\LoggerFactory;
use JsonException;
use Monolog\Logger;
use Throwable;

/**
 * Punto único de traducción de excepciones a respuestas HTTP.
 *
 * Registrado como handler global mediante set_exception_handler() en index.php.
 * Garantiza un formato de error consistente en toda la API:
 *
 * {
 *   "error": {
 *     "code": "ENTITY_NOT_FOUND",       <- legible por máquina (frontend)
 *     "message": "El recurso ..."        <- legible por humanos (usuario)
 *   }
 * }
 *
 * Estrategia de logging:
 *  - error   → Throwable desconocido (5xx): bug real, traza completa
 *  - warning → AppException que no es ValidationException: flujo esperado
 *              pero útil para monitoreo (404, 409, 422 de negocio, etc.)
 *  - sin log → ValidationException y JsonException: errores del cliente,
 *              no accionables del lado del servidor
 *
 * Los mensajes enviados al cliente NUNCA exponen IDs internos, rutas
 * ni detalles de implementación. Esa información queda en los logs.
 */
class ExceptionHandler
{
    private readonly Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? LoggerFactory::create('exceptions');
    }

    public function handle(Throwable $e): void
    {
        // JSON mal formado en el body del request — error del cliente, sin log
        if ($e instanceof JsonException) {
            JsonHelper::jsonResponse([
                'error' => [
                    'code' => 'INVALID_JSON',
                    'message' => 'El cuerpo de la solicitud no es un JSON válido',
                ],
            ], 400);
            return;
        }

        // Excepciones de dominio propias de la aplicación
        if ($e instanceof AppException) {
            $body = [
                'error' => [
                    'code' => $e->getErrorCode(),
                    'message' => $e->getSafeMessage(),
                ],
            ];

            // ValidationException agrega los errores por campo
            if ($e instanceof ValidationException) {
                $body['error']['fields'] = $e->getErrors();
            }

            // BusinessRuleException agrega el campo que falló (si existe)
            if ($e instanceof BusinessRuleException && $e->getField() !== null) {
                $body['error']['field'] = $e->getField();
            }

            // Las ValidationException son errores del cliente (input incorrecto),
            // loguear cada una generaría ruido sin valor operacional
            if (!($e instanceof ValidationException)) {
                $this->logger->warning($e->getSafeMessage(), [
                    'code' => $e->getErrorCode(),
                    'http_status' => $e->getHttpStatus(),
                    'exception' => get_class($e),
                ]);
            }

            JsonHelper::jsonResponse($body, $e->getHttpStatus());
            return;
        }

        // Cualquier error inesperado: el cliente recibe solo un mensaje genérico,
        // pero se registra la traza completa para diagnóstico
        $this->logger->error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        JsonHelper::jsonResponse([
            'error' => [
                'code' => 'INTERNAL_SERVER_ERROR',
                'message' => 'Error interno del servidor',
            ],
        ], 500);
    }
}
