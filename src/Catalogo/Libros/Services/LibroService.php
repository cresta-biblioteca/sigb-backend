<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Services;

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Articulos\Validators\ArticuloRequestValidator;
use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Mappers\LibroMapper;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Validators\LibroRequestValidator;

class LibroService
{
    public function __construct(
        private LibroRepository $repository,
        private ArticuloService $articuloService
    ) {
    }

    /**
     * @return LibroResponse[]
     */
    public function getAll(): array
    {
        $libros = $this->repository->findAll();
        return array_map(fn($libro) => LibroMapper::toLibroResponse($libro), $libros);
    }

    public function getById(int $id): LibroResponse
    {
        $libro = $this->repository->findById($id);

        if ($libro === null) {
            throw new LibroNotFoundException($id);
        }

        return LibroMapper::toLibroResponse($libro);
    }

    /**
     * Crea un libro completo con artículo y libro en una sola operación
     */
    public function create(array $articuloData, array $libroData): LibroResponse
    {
        // Validar datos del artículo
        ArticuloRequestValidator::validate($articuloData);

        // Validar datos del libro
        $allLibroData = array_merge($libroData, ['articulo_id' => 1]); // articulo_id temporal para validación
        LibroRequestValidator::validate($allLibroData);

        if (isset($libroData['isbn']) && $this->repository->existsByIsbn($libroData['isbn'])) {
            throw new LibroAlreadyExistsException($libroData['isbn'], 'isbn');
        }

        $articuloRequest = new ArticuloRequest(
            titulo: $articuloData['titulo'],
            anioPublicacion: (int) $articuloData['anio_publicacion'],
            tipoDocumentoId: (int) $articuloData['tipo_documento_id'],
            idioma: $articuloData['idioma'] ?? 'es'
        );

        $articuloResponse = $this->articuloService->create($articuloRequest);

        $libroRequest = new LibroRequest(
            articuloId: $articuloResponse->id,
            isbn: $libroData['isbn'],
            exportMarc: $libroData['export_marc'],
            autor: $libroData['autor'] ?? null,
            autores: $libroData['autores'] ?? null,
            colaboradores: $libroData['colaboradores'] ?? null,
            tituloInformativo: $libroData['titulo_informativo'] ?? null,
            cdu: isset($libroData['cdu']) ? (int) $libroData['cdu'] : null
        );

        $libro = Libro::create(
            $libroRequest->articuloId,
            $libroRequest->isbn,
            $libroRequest->exportMarc,
            $libroRequest->autor,
            $libroRequest->autores,
            $libroRequest->colaboradores,
            $libroRequest->tituloInformativo,
            $libroRequest->cdu
        );

        $this->repository->save($libro);

        $savedLibro = $this->repository->findById($articuloResponse->id);

        return LibroMapper::toLibroResponse($savedLibro);
    }

    public function updateLibro(int $id, LibroRequest $request): LibroResponse
    {
        LibroRequestValidator::validateId($id);

        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new LibroNotFoundException($id);
        }

        if ($this->repository->existsByIsbn($request->isbn, $id)) {
            throw new LibroAlreadyExistsException($request->isbn, 'isbn');
        }

        $libro = Libro::create(
            $request->articuloId,
            $request->isbn,
            $request->exportMarc,
            $request->autor,
            $request->autores,
            $request->colaboradores,
            $request->tituloInformativo,
            $request->cdu
        );

        $this->repository->update($libro);

        $updated = $this->repository->findById($id);

        return LibroMapper::toLibroResponse($updated);
    }

    public function deleteLibro(int $id): void
    {
        LibroRequestValidator::validateId($id);

        if ($this->repository->findById($id) === null) {
            throw new LibroNotFoundException($id);
        }

        $this->repository->delete($id);
    }

    /**
     * @param array<string, mixed> $filters
     * @return LibroResponse[]
     */
    public function search(array $filters): array
    {
        LibroRequestValidator::validateSearchParams($filters);

        $libros = $this->repository->search($filters);
        return array_map(fn($libro) => LibroMapper::toLibroResponse($libro), $libros);
    }


    public function searchPaginated(array $filters, int $page, int $perPage): array
    {
        LibroRequestValidator::validateSearchParams($filters);
        LibroRequestValidator::validatePaginationParams(['page' => $page, 'per_page' => $perPage]);
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));

        $total = $this->repository->countSearch($filters);
        $libros = $this->repository->searchPaginated($filters, $page, $perPage);

        $items = array_map(fn($libro) => LibroMapper::toLibroResponse($libro), $libros);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            ],
        ];
    }
}
