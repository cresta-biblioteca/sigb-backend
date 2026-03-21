<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Services;

use App\Catalogo\Libros\Dtos\Request\CrearLibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Mappers\LibroMapper;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Shared\Exceptions\BusinessRuleException;

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
    public function create(CrearLibroRequest $request): LibroResponse
    {
        $this->validateIsbnIssnExclusivity($request->getIsbn(), $request->getIssn());

        if ($request->getIsbn() !== null && $this->repository->existsByIsbn($request->getIsbn())) {
            throw new LibroAlreadyExistsException($request->getIsbn(), 'isbn');
        }

        if ($request->getIssn() !== null && $this->repository->existsByIssn($request->getIssn())) {
            throw new LibroAlreadyExistsException($request->getIssn(), 'issn');
        }

        $libro = Libro::create(
            articuloId: $request->getArticuloId(),
            exportMarc: $request->getExportMarc(),
            isbn: $request->getIsbn(),
            issn: $request->getIssn(),
            paginas: $request->getPaginas(),
            autor: $request->getAutor(),
            autores: $request->getAutores(),
            colaboradores: $request->getColaboradores(),
            tituloInformativo: $request->getTituloInformativo(),
            cdu: $request->getCdu(),
            editorial: $request->getEditorial(),
            lugarDePublicacion: $request->getLugarDePublicacion()
        );

        $savedLibro = $this->repository->insertLibro($libro);

        return LibroMapper::toLibroResponse($savedLibro);
    }

    public function updateLibro(int $id, CrearLibroRequest $request): LibroResponse
    {
        // Obtener libro existente para verificar que existe y preservar inmutables
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new LibroNotFoundException($id);
        }

        // Preservar campos inmutables del libro existente, usar campos editables del request
        $libro = Libro::create(
            articuloId: $existing->getArticuloId(),
            exportMarc: $existing->getExportMarc(),
            isbn: $existing->getIsbn(),
            issn: $existing->getIssn(),
            paginas: $request->getPaginas() ?? $existing->getPaginas(),
            autor: $request->getAutor() ?? $existing->getAutor(),
            autores: $request->getAutores() ?? $existing->getAutores(),
            colaboradores: $request->getColaboradores() ?? $existing->getColaboradores(),
            tituloInformativo: $request->getTituloInformativo() ?? $existing->getTituloInformativo(),
            cdu: $request->getCdu() ?? $existing->getCdu(),
            editorial: $request->getEditorial() ?? $existing->getEditorial(),
            lugarDePublicacion: $request->getLugarDePublicacion() ?? $existing->getLugarDePublicacion()
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


    private function validateIsbnIssnExclusivity(?string $isbn, ?string $issn): void
    {
        if ($isbn !== null && $issn !== null) {
            throw new BusinessRuleException(
                'BUSINESS_RULE_VIOLATION',
                'Un libro no puede tener ISBN y ISSN a la vez',
                field: 'isbn'
            );
        }
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
