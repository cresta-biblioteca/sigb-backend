<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Exceptions\AppException;
use App\Shared\Exceptions\BusinessRuleException;
use App\Shared\Exceptions\ValidationException;
use JsonException;
use Throwable;

/**
 * Punto único de traducción de excepciones a respuestas HTTP.
 *
 * Todos los controllers delegan su catch-all aquí, garantizando
 * un formato de error consistente en toda la API:
 *
 * {
 *   "error": {
 *     "code": "ENTITY_NOT_FOUND",       <- legible por máquina (frontend)
 *     "message": "El recurso ..."        <- legible por humanos (usuario)
 *   }
 * }
 *
 * Los mensajes enviados al cliente NUNCA exponen IDs internos, rutas
 * ni detalles de implementación. Esa información queda en los logs.
 */
class ExceptionHandler
{
    public static function handle(Throwable $e, string $context = ''): void
    {
        // JSON mal formado en el body del request
        if ($e instanceof JsonException) {
            JsonHelper::jsonResponse([
                'error' => [
                    'code'    => 'INVALID_JSON',
                    'message' => 'El cuerpo de la solicitud no es un JSON válido',
                ],
            ], 400);
            return;
        }

        // Excepciones de dominio propias de la aplicación
        if ($e instanceof AppException) {
            $body = [
                'error' => [
                    'code'    => $e->getErrorCode(),
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

            JsonHelper::jsonResponse($body, $e->getHttpStatus());
            return;
        }

        // Cualquier error inesperado: se loguea con contexto pero el cliente
        // recibe solo un mensaje genérico sin internals
        $logPrefix = $context !== '' ? "[{$context}]" : '[SIGB]';
        error_log("{$logPrefix} " . get_class($e) . ': ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine());

        JsonHelper::jsonResponse([
            'error' => [
                'code'    => 'INTERNAL_SERVER_ERROR',
                'message' => 'Error interno del servidor',
            ],
        ], 500);
    }
}
