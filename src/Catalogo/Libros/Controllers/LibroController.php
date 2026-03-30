<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Controllers;

use App\Catalogo\Articulos\Validators\ArticuloRequestValidator;
use App\Catalogo\Libros\Dtos\Request\CreateLibroRequest;
use App\Catalogo\Libros\Dtos\Request\PatchLibroRequest;
use App\Catalogo\Libros\Services\LibroService;
use App\Catalogo\Libros\Validators\LibroRequestValidator;
use App\Shared\Http\JsonHelper;

readonly class LibroController
{
    public function __construct(
        private LibroService $libroService
    ) {
    }

    /**
     * GET /libros/{id}
     */
    public function getById($id): void
    {
        LibroRequestValidator::validateId((int)$id);
        $libro = $this->libroService->getById((int)$id);
        JsonHelper::jsonResponse(['data' => $libro]);
    }

    /**
     * POST /libros
     * Crea un libro completo con artículo y libro en una sola transacción
     */
    public function create(): void
    {
        $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $articuloData = $input['articulo'] ?? [];
        $libroData = $input['libro'] ?? [];

        ArticuloRequestValidator::validate($articuloData);
        LibroRequestValidator::validate($libroData);

        $request = CreateLibroRequest::fromArray($articuloData, $libroData);

        $libro = $this->libroService->create($request);

        JsonHelper::jsonResponse(['data' => $libro, 'message' => 'Libro creado exitosamente'], 201);
    }

    /**
     * PUT/PATCH /libros/{id}
     */
    public function updateLibro($id): void
    {
        LibroRequestValidator::validateId((int)$id);

        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        LibroRequestValidator::validatePatch($data);

        $request = PatchLibroRequest::fromRequest($data);

        $response = $this->libroService->updateLibro((int)$id, $request);

        JsonHelper::jsonResponse(['data' => $response, 'message' => 'Libro actualizado exitosamente']);
    }

    /**
     * DELETE /libros/{id}
     */
    public function deleteLibro($id): void
    {
        LibroRequestValidator::validateId((int)$id);
        $this->libroService->deleteLibro((int)$id);
        JsonHelper::jsonResponse(['message' => 'Libro eliminado exitosamente']);
    }

    /**
     * GET /libros
     */
    public function searchPaginated(): void
    {
        // Aplica valores por defecto ante la ausencia de paginacion y filtros de sorting
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $sortBy = $_GET['sort_by'] ?? 'titulo';
        $sortDir = $_GET['sort_dir'] ?? 'asc';
        $filters = array_filter(
            $_GET,
            fn($key) => !in_array($key, ['page', 'per_page', 'sort_by', 'sort_dir'], true),
            ARRAY_FILTER_USE_KEY
        );

        LibroRequestValidator::validateSearchParams($_GET);

        $result = $this->libroService->searchPaginated($filters, $page, $perPage, $sortBy, $sortDir);

        JsonHelper::jsonResponse([
            'data' => $result['items'],
            'pagination' => $result['pagination'],
        ]);
    }
}
