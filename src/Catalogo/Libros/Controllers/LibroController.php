<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Controllers;

use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Libros\Services\LibroService;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Libros\Validators\LibroRequestValidator;
use App\Catalogo\Articulos\Validators\ArticuloRequestValidator;
use App\Shared\Http\ExceptionHandler;
use App\Shared\Http\JsonHelper;
use Throwable;

class LibroController
{
    public function __construct(
        private LibroService $libroService,
        private ArticuloService $articuloService
    ) {
    }

    /**
     * GET /libros
     */
    public function getAll(): void
    {
        try {
            $libros = $this->libroService->getAll();
            JsonHelper::jsonResponse(['data' => $libros]);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'LibroController::getAll');
        }
    }

    /**
     * GET /libros/{id}
     */
    public function getById($id): void
    {
        try {
            LibroRequestValidator::validateId((int) $id);
            $libro = $this->libroService->getById((int) $id);
            JsonHelper::jsonResponse(['data' => $libro]);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'LibroController::getById');
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

            ArticuloRequestValidator::validate($articuloData);
            LibroRequestValidator::validate($libroData);

            $articuloRequest = new ArticuloRequest(
                titulo: $articuloData['titulo'],
                anioPublicacion: (int) $articuloData['anio_publicacion'],
                tipoDocumentoId: (int) $articuloData['tipo_documento_id'],
                idioma: $articuloData['idioma'] ?? 'es'
            );

            $articuloResponse = $this->articuloService->create($articuloRequest);

            $libroRequest = new LibroRequest(
                articuloId: $articuloResponse->getId(),
                isbn: $libroData['isbn'],
                exportMarc: $libroData['export_marc'],
                autor: $libroData['autor'] ?? null,
                autores: $libroData['autores'] ?? null,
                colaboradores: $libroData['colaboradores'] ?? null,
                tituloInformativo: $libroData['titulo_informativo'] ?? null,
                cdu: isset($libroData['cdu']) ? (int) $libroData['cdu'] : null
            );

            $libro = $this->libroService->create($libroRequest);

            JsonHelper::jsonResponse(['data' => $libro, 'message' => 'Libro creado exitosamente'], 201);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'LibroController::create');
        }
    }

    /**
     * PUT/PATCH /libros/{id}
     */
    public function updateLibro($id): void
    {
        try {
            LibroRequestValidator::validateId((int) $id);

            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            LibroRequestValidator::validatePatch($input);

            $request = new LibroRequest(
                articuloId: 0,
                isbn: '',
                exportMarc: '',
                autor: $input['autor'] ?? null,
                autores: $input['autores'] ?? null,
                colaboradores: $input['colaboradores'] ?? null,
                tituloInformativo: $input['titulo_informativo'] ?? null,
                cdu: isset($input['cdu']) ? (int) $input['cdu'] : null
            );

            $response = $this->libroService->updateLibro((int) $id, $request);

            JsonHelper::jsonResponse(['data' => $response, 'message' => 'Libro actualizado exitosamente']);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'LibroController::updateLibro');
        }
    }

    /**
     * DELETE /libros/{id}
     */
    public function deleteLibro($id): void
    {
        try {
            LibroRequestValidator::validateId((int) $id);
            $this->libroService->deleteLibro((int) $id);
            JsonHelper::jsonResponse(['message' => 'Libro eliminado exitosamente']);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'LibroController::deleteLibro');
        }
    }

    /**
     * GET /libros/search
     */
    public function search(): void
    {
        try {
            LibroRequestValidator::validateSearchParams($_GET);
            $libros = $this->libroService->search($_GET);
            JsonHelper::jsonResponse(['data' => $libros]);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'LibroController::search');
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

            $result = $this->libroService->searchPaginated($filters, $page, $perPage);

            JsonHelper::jsonResponse([
                'data' => $result['items'],
                'pagination' => $result['pagination'],
            ]);
        } catch (Throwable $e) {
            ExceptionHandler::handle($e, 'LibroController::searchPaginated');
        }
    }
}
