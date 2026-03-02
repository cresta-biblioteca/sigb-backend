<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Controllers;

use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Services\LibroService;
use App\Catalogo\Libros\Validators\LibroRequestValidator;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Http\JsonHelper;
use Exception;
use JsonException;

class LibroController
{
    public function __construct(private LibroService $service)
    {
    }

    /**
     * GET /libros
     */
    public function getAll(): void
    {
        try {
            $libros = $this->service->getAll();

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $libros,
            ]);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[LibroController::getAll] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * GET /libros/{id}
     */
    public function getById($id): void
    {
        try {
            LibroRequestValidator::validateId((int) $id);

            $libro = $this->service->getById((int) $id);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $libro,
            ]);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (LibroNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[LibroController::getById] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * POST /libros
     * Crea un libro completo con artículo y libro en una sola operación
     */
    public function create(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            $articuloData = $input['articulo'] ?? [];
            $libroData = $input['libro'] ?? [];

            $libro = $this->service->create($articuloData, $libroData);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $libro,
                'message' => 'Libro creado exitosamente'
            ], 201);
        } catch (JsonException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => 'El formato JSON es inválido'
            ], 400);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (LibroAlreadyExistsException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 409);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[LibroController::create] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * PUT /libros/{id}
     */
    public function updateLibro($id): void
    {
        try {
            LibroRequestValidator::validateId((int) $id);

            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            LibroRequestValidator::validate($input);

            $request = new LibroRequest(
                articuloId: (int) $input['articulo_id'],
                isbn: $input['isbn'],
                exportMarc: $input['export_marc'],
                autor: $input['autor'] ?? null,
                autores: $input['autores'] ?? null,
                colaboradores: $input['colaboradores'] ?? null,
                tituloInformativo: $input['titulo_informativo'] ?? null,
                cdu: isset($input['cdu']) ? (int) $input['cdu'] : null
            );

            $libro = $this->service->updateLibro((int) $id, $request);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $libro,
                'message' => 'Libro actualizado exitosamente'
            ]);
        } catch (JsonException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => 'El formato JSON es inválido'
            ], 400);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (LibroNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (LibroAlreadyExistsException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 409);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[LibroController::updateLibro] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * DELETE /libros/{id}
     */
    public function deleteLibro($id): void
    {
        try {
            LibroRequestValidator::validateId((int) $id);

            $this->service->deleteLibro((int) $id);

            JsonHelper::jsonResponse([
                'error' => false,
                'message' => 'Libro eliminado exitosamente'
            ]);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (LibroNotFoundException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[LibroController::deleteLibro] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * GET /libros/search
     */
    public function search(): void
    {
        try {
            LibroRequestValidator::validateSearchParams($_GET);

            $libros = $this->service->search($_GET);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $libros,
            ]);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[LibroController::search] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }

    /**
     * GET /libros/search/paginated
     */
    public function searchPaginated(): void
    {
        try {
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;
            $filters = array_filter($_GET, fn($key) => !in_array($key, ['page', 'per_page']), ARRAY_FILTER_USE_KEY);

            LibroRequestValidator::validateSearchParams($_GET);
            LibroRequestValidator::validatePaginationParams($_GET);

            $result = $this->service->searchPaginated($filters, $page, $perPage);

            JsonHelper::jsonResponse([
                'error' => false,
                'data' => $result['items'],
                'pagination' => $result['pagination'],
            ]);
        } catch (ValidationException $e) {
            JsonHelper::jsonResponse([
                'error' => true,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        } catch (Exception $e) {
            JsonHelper::jsonResponse(['message' => 'Error interno del servidor'], 500);
            error_log("[LibroController::searchPaginated] {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}");
        }
    }
}
