<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

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
            JsonHelper::jsonResponse($articulos, 200);
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
            ArticuloRequestValidator::validateId((int) $id);

            $articulo = $this->service->getById((int) $id);
            JsonHelper::jsonResponse($articulo, 200);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::getById] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * PATCH /articulos/{id}
     */
    public function patchArticulo($id): void
    {
        try {
            ArticuloRequestValidator::validateId((int) $id);

            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            ArticuloRequestValidator::validatePatch($input);

            $articulo = $this->service->patchArticulo((int) $id, $input);
            JsonHelper::jsonResponse($articulo, 200);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (BusinessValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
                'field' => $e->getField()
            ], 422);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (JsonException) {
            JsonHelper::jsonResponse([
                'message' => 'JSON inválido',
            ], 400);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::patchArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * DELETE /articulos/{id}
     */
    public function deleteArticulo($id): void
    {
        try {
            ArticuloRequestValidator::validateId((int) $id);

            $this->service->deleteArticulo((int) $id);

            http_response_code(204);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => 'Datos de entrada no válidos',
                'errors' => $e->getErrors()
            ], 400);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        } catch (BusinessValidationException $e) {
            JsonHelper::jsonResponse([
                'message' => $e->getMessage(),
                'field' => $e->getField()
            ], 409);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[ArticuloController::deleteArticulo] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }
}
