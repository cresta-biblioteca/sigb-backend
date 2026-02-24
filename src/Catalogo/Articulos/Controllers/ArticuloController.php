<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Controllers;

use App\Catalogo\Articulos\Dtos\Request\ArticuloCatalogFilterRequest;
use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Mappers\ArticuloMapper;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Articulos\Validators\ArticuloCatalogQueryValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use JsonException;
use Throwable;

class ArticuloController
{
    public function __construct(private ArticuloService $service)
    {
    }

    /**
     * GET /articulos
     * Soporta filtros opcionales por query params
     */
    public function getAll(): void
    {
        try {
            /** @var ArticuloCatalogFilterRequest $catalogFilterRequest */
            $catalogFilterRequest = ArticuloCatalogQueryValidator::fromQuery($_GET);

            $result = $this->service->listPaginated(
                $catalogFilterRequest->filters,
                $catalogFilterRequest->page,
                $catalogFilterRequest->perPage
            );

            $response = array_map(
                fn($articuloDto) => $articuloDto->toArray(),
                $result['items']
            );

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $response,
                'pagination' => $result['pagination'],
            ]);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (Throwable $e) {
            $this->handleServerError($e);
        }
    }

    /**
     * GET /articulos/{id}
     */
    public function showById(int $id): void
    {
        try {
            $articulo = $this->service->getById($id);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $articulo->toArray(),
            ]);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            $this->handleServerError($e);
        }
    }

    /**
     * POST /articulos
     */
    public function create(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            $request = ArticuloMapper::fromArray($input);

            $articulo = $this->service->create($request);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $articulo->toArray(),
            ], 201);
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
        } catch (JsonException) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => 'JSON inválido',
            ], 400);
        } catch (Throwable $e) {
            $this->handleServerError($e);
        }
    }

    /**
     * PUT /articulos/{id}
     */
    public function update(int $id): void
    {
        try {
            if ($id < 1) {
                JsonHelper::jsonResponse([
                    'error' => true,
                    'message' => 'ID inválido. El ID debe ser un entero positivo mayor que 0.',
                ], 422);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            $request = ArticuloMapper::fromArray($input);

            $articulo = $this->service->update($id, $request);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $articulo->toArray(),
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
        } catch (Throwable $e) {
            $this->handleServerError($e);
        }
    }

    /**
     * DELETE /articulos/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $this->service->delete($id);

            JsonHelper::jsonResponse([
                'error' => false,
                'message' => 'Articulo eliminado',
            ]);
        } catch (ArticuloNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            $this->handleServerError($e);
        }
    }

    private function handleServerError(Throwable $e): void
    {
        JsonHelper::jsonResponse([
            'error' => true,
            'message' => 'Error interno del servidor',
        ], 500);

        error_log($e->getMessage());
    }
}
