<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Services;

use App\Catalogo\Libros\Dtos\Request\CrearLibroRequest;
use App\Catalogo\Libros\Dtos\Request\PatchLibroRequest;
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
        private readonly LibroRepository $repository
    )
    {
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

    public function updateLibro(int $id, PatchLibroRequest $request): LibroResponse
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new LibroNotFoundException($id);
        }

        $apply = [
            'isbn' => fn() => $existing->setIsbn($request->isbn),
            'issn' => fn() => $existing->setIssn($request->issn),
            'paginas' => fn() => $existing->setPaginas($request->paginas),
            'autor' => fn() => $existing->setAutor($request->autor),
            'autores' => fn() => $existing->setAutores($request->autores),
            'colaboradores' => fn() => $existing->setColaboradores($request->colaboradores),
            'titulo_informativo' => fn() => $existing->setTituloInformativo($request->tituloInformativo),
            'cdu' => fn() => $existing->setCdu($request->cdu),
            'editorial' => fn() => $existing->setEditorial($request->editorial),
            'lugar_de_publicacion' => fn() => $existing->setLugarDePublicacion($request->lugarDePublicacion),
        ];

        foreach ($request->provided as $field) {
            if (isset($apply[$field])) {
                $apply[$field]();
            }
        }

        $updated = $this->repository->updateLibro($id, $existing);

        return LibroMapper::toLibroResponse($updated);
    }

    public function deleteLibro(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw new LibroNotFoundException($id);
        }

        $this->repository->delete($id);
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

    public function searchPaginated(array $filters, int $page, int $perPage, string $sortBy = 'titulo', string $sortDir = 'asc'): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));

        $total = $this->repository->countSearch($filters);
        $libros = $this->repository->searchPaginated($filters, $page, $perPage, $sortBy, $sortDir);

        $items = array_map(fn($libro) => LibroMapper::toLibroResponse($libro), $libros);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int)ceil($total / $perPage) : 1,
            ],
        ];
    }
}
