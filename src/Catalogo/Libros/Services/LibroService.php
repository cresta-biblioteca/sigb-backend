<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Services;

use App\Catalogo\Articulos\Models\Articulo;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Articulos\Services\ArticuloService;
use App\Catalogo\Libros\Dtos\Request\CreateLibroRequest;
use App\Catalogo\Libros\Dtos\Request\PatchLibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Mappers\LibroMapper;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Catalogo\Libros\Repositories\PersonaRepository;
use App\Shared\Enums\TipoArticulo;
use App\Shared\Exceptions\BusinessRuleException;
use PDO;

readonly class LibroService
{
    public function __construct(
        private LibroRepository $repository,
        private ArticuloRepository $articuloRepository,
        private PersonaRepository $personaRepository,
        private ArticuloService $articuloService,
        private PDO $pdo
    ) {
    }

    public function getById(int $id): LibroResponse
    {
        $libro = $this->repository->findById($id);

        if ($libro === null) {
            throw new LibroNotFoundException();
        }

        return LibroMapper::toLibroResponse($libro);
    }

    public function create(CreateLibroRequest $request): LibroResponse
    {
        $this->validateIsbnIssnExclusivity($request->isbn, $request->issn);

        if ($request->isbn !== null && $this->repository->existsByIsbn($request->isbn)) {
            throw new LibroAlreadyExistsException();
        }

        if ($request->issn !== null && $this->repository->existsByIssn($request->issn)) {
            throw new LibroAlreadyExistsException();
        }

        $inTransaction = $this->pdo->inTransaction();

        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $articulo = Articulo::create(
                titulo: $request->titulo,
                anioPublicacion: $request->anioPublicacion,
                tipo: TipoArticulo::LIBRO->value,
                idioma: $request->idioma,
                descripcion: $request->descripcion
            );

            $savedArticulo = $this->articuloRepository->insertArticulo($articulo);

            $libro = Libro::create(
                articuloId: $savedArticulo->getId(),
                isbn: $request->isbn,
                issn: $request->issn,
                paginas: $request->paginas,
                tituloInformativo: $request->tituloInformativo,
                cdu: $request->cdu,
                editorial: $request->editorial,
                lugarDePublicacion: $request->lugarDePublicacion,
                edicion: $request->edicion,
                dimensiones: $request->dimensiones,
                ilustraciones: $request->ilustraciones,
                serie: $request->serie,
                numeroSerie: $request->numeroSerie,
                notas: $request->notas,
                paisPublicacion: $request->paisPublicacion,
            );

            $this->repository->insertLibro($libro);

            // Procesar personas
            $this->processPersonas($savedArticulo->getId(), $request->personas);

            if (!$inTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if (!$inTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        // Re-fetch para devolver libro completo con personas
        return LibroMapper::toLibroResponse($this->repository->findById($savedArticulo->getId()));
    }

    public function updateLibro(int $id, PatchLibroRequest $request): LibroResponse
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new LibroNotFoundException();
        }

        $inTransaction = $this->pdo->inTransaction();

        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $apply = [
                'isbn' => fn() => $existing->setIsbn($request->isbn),
                'issn' => fn() => $existing->setIssn($request->issn),
                'paginas' => fn() => $existing->setPaginas($request->paginas),
                'titulo_informativo' => fn() => $existing->setTituloInformativo($request->tituloInformativo),
                'cdu' => fn() => $existing->setCdu($request->cdu),
                'editorial' => fn() => $existing->setEditorial($request->editorial),
                'lugar_de_publicacion' => fn() => $existing->setLugarDePublicacion($request->lugarDePublicacion),
                'edicion' => fn() => $existing->setEdicion($request->edicion),
                'dimensiones' => fn() => $existing->setDimensiones($request->dimensiones),
                'ilustraciones' => fn() => $existing->setIlustraciones($request->ilustraciones),
                'serie' => fn() => $existing->setSerie($request->serie),
                'numero_serie' => fn() => $existing->setNumeroSerie($request->numeroSerie),
                'notas' => fn() => $existing->setNotas($request->notas),
                'pais_publicacion' => fn() => $existing->setPaisPublicacion($request->paisPublicacion),
            ];

            foreach ($request->provided as $field) {
                if (isset($apply[$field])) {
                    $apply[$field]();
                }
            }

            $this->repository->updateLibro($id, $existing);

            // Si personas está en provided, reemplazo completo
            if ($request->isProvided('personas') && $request->personas !== null) {
                $this->processPersonas($id, $request->personas);
            }

            if (!$inTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if (!$inTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        // Re-fetch
        return LibroMapper::toLibroResponse($this->repository->findById($id));
    }

    public function deleteLibro(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw new LibroNotFoundException();
        }

        $this->articuloService->deleteArticulo($id);
    }

    private function validateIsbnIssnExclusivity(?string $isbn, ?string $issn): void
    {
        if ($isbn !== null && $issn !== null) {
            throw new BusinessRuleException('Un libro no puede tener ISBN y ISSN a la vez', 'isbn');
        }
    }

    /**
     * @param array<int, array{nombre: string, apellido: string, rol: string}> $personasData
     */
    private function processPersonas(int $libroId, array $personasData): void
    {
        $syncData = [];

        foreach ($personasData as $index => $data) {
            $persona = $this->personaRepository->findOrCreate(
                trim($data['nombre']),
                trim($data['apellido'])
            );

            $syncData[] = [
                'persona_id' => $persona->getId(),
                'rol' => $data['rol'],
                'orden' => $data['orden'] ?? $index,
            ];
        }

        $this->repository->syncPersonas($libroId, $syncData);
    }

    public function searchPaginated(
        array $filters,
        int $page,
        int $perPage,
        string $sortBy = 'titulo',
        string $sortDir = 'asc'
    ): array {
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
