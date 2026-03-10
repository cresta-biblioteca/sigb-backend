<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Services;

use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Mappers\LibroMapper;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Repositories\LibroRepository;

class LibroService
{
    public function __construct(
        private LibroRepository $repository
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
     * Crea un nuevo libro
     */
    public function create(LibroRequest $request): LibroResponse
    {
        if ($this->repository->existsByIsbn($request->getIsbn())) {
            throw new LibroAlreadyExistsException($request->getIsbn(), 'isbn');
        }

        $libro = Libro::create(
            $request->getArticuloId(),
            $request->getIsbn(),
            $request->getExportMarc(),
            $request->getAutor(),
            $request->getAutores(),
            $request->getColaboradores(),
            $request->getTituloInformativo(),
            $request->getCdu()
        );

        $savedLibro = $this->repository->insertLibro($libro);

        return LibroMapper::toLibroResponse($savedLibro);
    }

    public function updateLibro(int $id, LibroRequest $request): LibroResponse
    {
        // Obtener libro existente para verificar que existe y preservar inmutables
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new LibroNotFoundException($id);
        }

        // Preservar campos inmutables del libro existente, usar campos editables del request
        $libro = Libro::create(
            $existing->getArticuloId(), // Inmutable - del existente
            $existing->getIsbn(), // Inmutable - del existente
            $existing->getExportMarc(), // Inmutable - del existente
            $request->getAutor() ?? $existing->getAutor(), // Editable - del request o existente si no se proporciona
            $request->getAutores() ?? $existing->getAutores(),
            $request->getColaboradores() ?? $existing->getColaboradores(),
            $request->getTituloInformativo() ?? $existing->getTituloInformativo(),
            $request->getCdu() ?? $existing->getCdu()
        );

        $updated = $this->repository->updateLibro($id, $libro);

        return LibroMapper::toLibroResponse($updated);
    }

    public function deleteLibro(int $id): void
    {
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
        $libros = $this->repository->search($filters);
        return array_map(fn($libro) => LibroMapper::toLibroResponse($libro), $libros);
    }


    public function searchPaginated(array $filters, int $page, int $perPage): array
    {
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
