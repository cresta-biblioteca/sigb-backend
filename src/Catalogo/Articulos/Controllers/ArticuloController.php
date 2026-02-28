<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Articulos\Validators\ArticuloRequestValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use Exception;
use JsonException;

class ArticuloController
{
    public function __construct(private ArticuloService $service)
    {
    }

    /**
     * GET /articulos
     */
    public function getAll(): void
    {
        try {
            $articulos = $this->service->getAll();

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $articulos,
            ]);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::getAll] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * GET /articulos/{id}
     */
    public function getById($id): void
    {
        try {
            ArticuloRequestValidator::validateId($id);

            $articulo = $this->service->getById((int) $id);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $articulo,
            ]);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::getById] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * PUT /articulos/{id}
     */
    public function updateArticulo($id): void
    {
        try {
            ArticuloRequestValidator::validateId($id);

            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            ArticuloRequestValidator::validate($input);

            $request = new ArticuloRequest(
                titulo: trim((string) $input['titulo']),
                anioPublicacion: (int) $input['anio_publicacion'],
                tipoDocumentoId: (int) $input['tipo_documento_id'],
                idioma: isset($input['idioma']) ? strtolower((string) $input['idioma']) : 'es'
            );

            $articulo = $this->service->updateArticulo((int) $id, $request);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $articulo,
            ]);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (BusinessValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => [
                    $e->getField() => [$e->getMessage()],
                ],
            ], 400);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (JsonException) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => 'JSON inválido',
            ], 400);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::updateArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * DELETE /articulos/{id}
     */
    public function deleteArticulo($id): void
    {
        try {
            ArticuloRequestValidator::validateId($id);

            $this->service->deleteArticulo((int) $id);

            http_response_code(204);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::deleteArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }
}
