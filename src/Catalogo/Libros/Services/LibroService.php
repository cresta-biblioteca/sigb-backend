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
	public function __construct(private LibroRepository $repository)
	{
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
	 */
	public function create(LibroRequest $request): LibroResponse
	{
		if ($this->repository->findByArticuloId($request->articuloId) !== null) {
			throw new LibroAlreadyExistsException($request->articuloId);
		}

		$libro = LibroMapper::requestToEntity($request);

		$this->repository->save($libro);

		$libroConArticulo = $this->repository->findById($libro->getArticuloId());

		if ($libroConArticulo === null) {
			throw new LibroNotFoundException($libro->getArticuloId());
		}

		return LibroMapper::toResponse($libroConArticulo);
	}

	/**
	 * @throws LibroNotFoundException
	 */
	public function update(int $id, LibroRequest $request): LibroResponse
	{
		$libroExistente = $this->repository->findById($id);

		if ($libroExistente === null) {
			throw new LibroNotFoundException($id);
		}

		$libro = LibroMapper::requestToEntity($request);
		$libro->setArticuloId($id);

		$this->repository->update($libro);

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
}
