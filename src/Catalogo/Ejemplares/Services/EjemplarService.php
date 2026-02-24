<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Services;

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarRequest;
use App\Catalogo\Ejemplares\Dtos\Response\EjemplarResponse;
use App\Catalogo\Ejemplares\Mappers\EjemplarMapper;
use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Catalogo\Ejemplares\Repositories\EjemplarRepository;
use App\Shared\Exceptions\EntityAlreadyExistsException;
use App\Shared\Exceptions\EntityNotFoundException;

class EjemplarService
{
	public function __construct(private EjemplarRepository $repository)
	{
	}

	/**
	 * @return EjemplarResponse[]
	 */
	public function getAll(): array
	{
		$ejemplares = $this->repository->findAll();

		return array_map(
			fn (Ejemplar $ejemplar) => EjemplarMapper::toEjemplarResponse($ejemplar),
			$ejemplares
		);
	}

	/**
	 * @throws EntityNotFoundException
	 */
	public function getById(int $id): EjemplarResponse
	{
		/** @var ?Ejemplar $ejemplar */
		$ejemplar = $this->repository->findById($id);

		if ($ejemplar === null) {
			throw new EntityNotFoundException('Ejemplar', $id);
		}

		return EjemplarMapper::toEjemplarResponse($ejemplar);
	}

	/**
	 * @throws EntityAlreadyExistsException
	 */
	public function create(EjemplarRequest $request): EjemplarResponse
	{
		if (
			$request->codigoBarras !== null
			&& $request->codigoBarras !== ''
			&& $this->repository->existsByCodigoBarras($request->codigoBarras)
		) {
			throw new EntityAlreadyExistsException('Ejemplar', 'codigo_barras', $request->codigoBarras);
		}

		$ejemplar = EjemplarMapper::fromEjemplarRequest($request);
		$created = $this->repository->save($ejemplar);

		return EjemplarMapper::toEjemplarResponse($created);
	}

	/**
	 * @throws EntityNotFoundException
	 * @throws EntityAlreadyExistsException
	 */
	public function update(int $id, EjemplarRequest $request): EjemplarResponse
	{
		/** @var ?Ejemplar $existing */
		$existing = $this->repository->findById($id);

		if ($existing === null) {
			throw new EntityNotFoundException('Ejemplar', $id);
		}

		if (
			$request->codigoBarras !== null
			&& $request->codigoBarras !== ''
			&& $this->repository->existsByCodigoBarras($request->codigoBarras, $id)
		) {
			throw new EntityAlreadyExistsException('Ejemplar', 'codigo_barras', $request->codigoBarras);
		}

		$ejemplar = EjemplarMapper::updateFromRequest($existing, $request);
		$this->repository->update($ejemplar);

		/** @var ?Ejemplar $updated */
		$updated = $this->repository->findById($id);

		if ($updated === null) {
			throw new EntityNotFoundException('Ejemplar', $id);
		}

		return EjemplarMapper::toEjemplarResponse($updated);
	}

	/**
	 * @throws EntityNotFoundException
	 */
	public function delete(int $id): void
	{
		if ($this->repository->findById($id) === null) {
			throw new EntityNotFoundException('Ejemplar', $id);
		}

		$this->repository->delete($id);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return EjemplarResponse[]
	 */
	public function search(array $filters): array
	{
		if (isset($filters['codigo_barras']) && $filters['codigo_barras'] !== '') {
			$ejemplar = $this->repository->findByCodigoBarras((string) $filters['codigo_barras']);

			if ($ejemplar === null) {
				return [];
			}

			return [EjemplarMapper::toEjemplarResponse($ejemplar)];
		}

		if (isset($filters['articulo_id'])) {
			$articuloId = (int) $filters['articulo_id'];

			$ejemplares = isset($filters['habilitado'])
				? $this->filterByHabilitado($this->repository->findByArticuloId($articuloId), (bool) $filters['habilitado'])
				: $this->repository->findByArticuloId($articuloId);

			return array_map(
				fn (Ejemplar $ejemplar) => EjemplarMapper::toEjemplarResponse($ejemplar),
				$ejemplares
			);
		}

		if (isset($filters['habilitado'])) {
			$ejemplares = $this->repository->findByHabilitado((bool) $filters['habilitado']);

			return array_map(
				fn (Ejemplar $ejemplar) => EjemplarMapper::toEjemplarResponse($ejemplar),
				$ejemplares
			);
		}

		return $this->getAll();
	}

	/**
	 * @return EjemplarResponse[]
	 */
	public function getByArticuloId(int $articuloId): array
	{
		$ejemplares = $this->repository->findByArticuloId($articuloId);

		return array_map(
			fn (Ejemplar $ejemplar) => EjemplarMapper::toEjemplarResponse($ejemplar),
			$ejemplares
		);
	}

	/**
	 * @return EjemplarResponse[]
	 */
	public function getHabilitadosByArticuloId(int $articuloId): array
	{
		$ejemplares = $this->repository->findHabilitadosByArticuloId($articuloId);

		return array_map(
			fn (Ejemplar $ejemplar) => EjemplarMapper::toEjemplarResponse($ejemplar),
			$ejemplares
		);
	}

	/**
	 * @throws EntityNotFoundException
	 */
	public function habilitar(int $id): EjemplarResponse
	{
		/** @var ?Ejemplar $ejemplar */
		$ejemplar = $this->repository->findById($id);

		if ($ejemplar === null) {
			throw new EntityNotFoundException('Ejemplar', $id);
		}

		$ejemplar->habilitar();
		$this->repository->update($ejemplar);

		return EjemplarMapper::toEjemplarResponse($ejemplar);
	}

	/**
	 * @throws EntityNotFoundException
	 */
	public function deshabilitar(int $id): EjemplarResponse
	{
		/** @var ?Ejemplar $ejemplar */
		$ejemplar = $this->repository->findById($id);

		if ($ejemplar === null) {
			throw new EntityNotFoundException('Ejemplar', $id);
		}

		$ejemplar->deshabilitar();
		$this->repository->update($ejemplar);

		return EjemplarMapper::toEjemplarResponse($ejemplar);
	}

	/**
	 * @param Ejemplar[] $ejemplares
	 * @return Ejemplar[]
	 */
	private function filterByHabilitado(array $ejemplares, bool $habilitado): array
	{
		return array_values(array_filter(
			$ejemplares,
			fn (Ejemplar $ejemplar): bool => $ejemplar->isHabilitado() === $habilitado
		));
	}
}
