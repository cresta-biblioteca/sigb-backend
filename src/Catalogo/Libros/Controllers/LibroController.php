<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Controllers;

use App\Catalogo\Libros\Dtos\Request\LibroCatalogFilterRequest;
use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Services\LibroService;
use App\Catalogo\Libros\Validators\LibroCatalogQueryValidator;
use App\Shared\Exceptions\BusinessValidationException;
use App\Shared\Exceptions\EntityAlreadyExistsException;
use App\Shared\Exceptions\EntityNotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use JsonException;
use Throwable;

class LibroController
{
    public function __construct(
        private LibroService $service
    ) {
    }

    /**
     * GET /libros
     * Soporta filtros opcionales por query params
     */
    public function listAll(): void
    {
        try {
            /** @var LibroCatalogFilterRequest $catalogFilterRequest */
            $catalogFilterRequest = LibroCatalogQueryValidator::fromQuery($_GET);

            $result = $this->service->listPaginated(
                $catalogFilterRequest->filters,
                $catalogFilterRequest->page,
                $catalogFilterRequest->perPage
            );

            $response = array_map(
                fn($libroDto) => $libroDto->toArray(),
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
     * GET /libros/{id}
     */
    public function showById(int $id): void
    {
        try {
            $libro = $this->service->getById($id);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $libro->toArray(),
            ]);
        } catch (LibroNotFoundException | EntityNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            $this->handleServerError($e);
        }
    }

    /**
     * POST /libros
     * Crea un nuevo libro.
     * Si no se informa articulo_id, crea el artículo en la misma operación.
     */
    public function create(): void
    {
        try {
            $data = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $libro = $this->service->createFromCatalog($data);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $libro->toArray(),
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
        } catch (LibroAlreadyExistsException | EntityAlreadyExistsException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 409);
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
     * PUT /libros/{id}
     * Actualiza un libro existente
     */
    public function update(int $id): void
    {
        try {
            $data = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $requestDto = LibroRequest::fromArray($data);

            $libro = $this->service->update($id, $requestDto);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $libro->toArray(),
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
                'field' => $e->getField(),
            ], 400);
        } catch (LibroNotFoundException | EntityNotFoundException $e) {
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
     * DELETE /libros/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $this->service->delete($id);

            JsonHelper::jsonResponse([
                'error' => false,
                'message' => 'Libro eliminado',
            ]);
        } catch (LibroNotFoundException | EntityNotFoundException $e) {
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