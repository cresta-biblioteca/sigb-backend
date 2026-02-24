<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Services;

use App\Catalogo\Articulos\Mappers\ArticuloMapper;
use App\Catalogo\Articulos\Repository\ArticuloRepository;
use App\Catalogo\Libros\Dtos\Request\LibroRequest;
use App\Catalogo\Libros\Dtos\Response\LibroResponse;
use App\Catalogo\Libros\Exceptions\LibroAlreadyExistsException;
use App\Catalogo\Libros\Exceptions\LibroNotFoundException;
use App\Catalogo\Libros\Mappers\LibroMapper;
use App\Catalogo\Libros\Models\Libro;
use App\Catalogo\Libros\Repositories\LibroRepository;
use App\Shared\Database\Connection;
use App\Shared\Exceptions\ValidationException;
use PDO;
use PDOException;
use Throwable;

class LibroService
{
	private ArticuloRepository $articuloRepository;
    private PDO $pdo;

	public function __construct(
        private LibroRepository $repository,
        ?ArticuloRepository $articuloRepository = null,
        ?PDO $pdo = null
    ) {
        $this->articuloRepository = $articuloRepository ?? new ArticuloRepository();
        $this->pdo = $pdo ?? Connection::getInstance();
	}

	/**
	 * Crea un libro y, opcionalmente, crea el artículo en la misma operación
	 * cuando no se provee articulo_id.
	 *
	 * Payload soportado:
	 * - Con artículo existente: { ...campos libro..., articulo_id }
	 * - Con artículo nuevo:
	 *   {
	 *     "articulo": { titulo, anio_publicacion, tipo_documento_id, idioma? },
	 *     "libro": { isbn, export_marc, autor?, ... }
	 *   }
	 *
	 * También acepta payload plano para artículo/libro sin anidar.
	 *
	 * @param array<string, mixed> $payload
	 * @throws LibroAlreadyExistsException
	 * @throws LibroNotFoundException
	 * @throws ValidationException
	 */
	public function createFromCatalog(array $payload): LibroResponse
	{
		$libroPayload = isset($payload['libro']) && is_array($payload['libro'])
			? $payload['libro']
			: $payload;

		$articuloId = isset($libroPayload['articulo_id'])
			? (int) $libroPayload['articulo_id']
			: null;

		if ($articuloId !== null && $articuloId > 0) {
			$requestDto = LibroRequest::fromArray($libroPayload);
			return $this->create($requestDto);
		}

		$articuloPayload = isset($payload['articulo']) && is_array($payload['articulo'])
			? $payload['articulo']
			: $payload;

		$this->pdo->beginTransaction();

		try {
			$articuloRequest = ArticuloMapper::fromArray($articuloPayload);
			$articulo = ArticuloMapper::fromArticuloRequest($articuloRequest);
			$articulo = $this->articuloRepository->insertArticulo($articulo);

			$libroPayload['articulo_id'] = $articulo->getId();
			$requestDto = LibroRequest::fromArray($libroPayload);
			$createdLibro = $this->create($requestDto);

			$this->pdo->commit();

			return $createdLibro;
		} catch (Throwable $e) {
			if ($this->pdo->inTransaction()) {
				$this->pdo->rollBack();
			}

			throw $e;
		}
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return LibroResponse[]
	 */
	public function listAll(array $filters = []): array
	{
		$libros = !empty($filters)
			? $this->repository->search($filters)
			: $this->repository->findAll();

		return array_map(
			fn (Libro $libro) => LibroMapper::toResponse($libro),
			$libros
		);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array{items: LibroResponse[], pagination: array<string, int>}
	 */
	public function listPaginated(array $filters, int $page, int $perPage): array
	{
		$page = max(1, $page);
		$perPage = max(1, min($perPage, 100));

		$total = $this->repository->countSearch($filters);
		$libros = $this->repository->searchPaginated($filters, $page, $perPage);

		$items = array_map(
			fn (Libro $libro) => LibroMapper::toResponse($libro),
			$libros
		);

		$totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

		return [
			'items' => $items,
			'pagination' => [
				'page' => $page,
				'per_page' => $perPage,
				'total' => $total,
				'total_pages' => $totalPages,
			],
		];
	}

	/**
	 * @throws LibroNotFoundException
	 */
	public function getById(int $id): LibroResponse
	{
		$libro = $this->repository->findById($id);

		if ($libro === null) {
			throw new LibroNotFoundException($id);
		}

		return LibroMapper::toResponse($libro);
	}

	/**
	 * @throws LibroAlreadyExistsException
	 * @throws LibroNotFoundException
	 */
	public function create(LibroRequest $request): LibroResponse
	{
		if ($this->repository->existsByArticuloId($request->articuloId)) {
			throw new LibroAlreadyExistsException($request->articuloId);
		}

		if ($this->repository->existsByIsbn($request->isbn)) {
			throw new LibroAlreadyExistsException($request->isbn, 'isbn');
		}

		$libro = LibroMapper::requestToEntity($request);

		try {
			$this->repository->save($libro);
		} catch (PDOException $e) {
			if ($this->isDuplicateKeyException($e)) {
				if (str_contains(strtolower($e->getMessage()), 'uq_libro_isbn')) {
					throw new LibroAlreadyExistsException($request->isbn, 'isbn');
				}

				throw new LibroAlreadyExistsException($request->articuloId);
			}

			throw $e;
		}

		$libroConArticulo = $this->repository->findById($libro->getArticuloId());

		if ($libroConArticulo === null) {
			throw new LibroNotFoundException($libro->getArticuloId());
		}

		return LibroMapper::toResponse($libroConArticulo);
	}

	/**
	 * @throws LibroNotFoundException
	 * @throws LibroAlreadyExistsException
	 */
	public function update(int $id, LibroRequest $request): LibroResponse
	{
		/** @var ?Libro $libroExistente */
		$libroExistente = $this->repository->findById($id);

		if ($libroExistente === null) {
			throw new LibroNotFoundException($id);
		}

		if ($this->repository->existsByIsbn($request->isbn, $id)) {
			throw new LibroAlreadyExistsException($request->isbn, 'isbn');
		}

		$libro = LibroMapper::requestToEntity($request);
		$libro->setArticuloId($id);

		try {
			$this->repository->update($libro);
		} catch (PDOException $e) {
			if ($this->isDuplicateKeyException($e)) {
				throw new LibroAlreadyExistsException($request->isbn, 'isbn');
			}

			throw $e;
		}

		$libroActualizado = $this->repository->findById($id);

		if ($libroActualizado === null) {
			throw new LibroNotFoundException($id);
		}

		return LibroMapper::toResponse($libroActualizado);
	}

	/**
	 * @throws LibroNotFoundException
	 */
	public function delete(int $id): void
	{
		if ($this->repository->findById($id) === null) {
			throw new LibroNotFoundException($id);
		}

		$this->repository->delete($id);
	}

	private function isDuplicateKeyException(PDOException $exception): bool
	{
		return $exception->getCode() === '23000';
	}
}
