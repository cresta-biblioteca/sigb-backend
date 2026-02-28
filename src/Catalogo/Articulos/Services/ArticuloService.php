<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Services;

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Articulos\Exceptions\ArticuloNotFoundException;
use App\Catalogo\Articulos\Mappers\ArticuloMapper;
use App\Catalogo\Articulos\Models\Articulo;
use App\Catalogo\Articulos\Repository\ArticuloRepository;

class ArticuloService
{
	public function __construct(private ArticuloRepository $repository)
	{
	}


	public function getAll(): array
	{
		$articulos = $this->repository->findAll();

		return array_map(
			fn (Articulo $articulo) => ArticuloMapper::toArticuloResponse($articulo),
			$articulos
		);
	}

	public function getById(int $id): ArticuloResponse
	{
		$articulo = $this->repository->findById($id);

		if ($articulo === null) {
			throw new ArticuloNotFoundException($id);
		}

		return ArticuloMapper::toArticuloResponse($articulo);
	}

	public function create(ArticuloRequest $request): ArticuloResponse
	{
		$articulo = Articulo::create(
			titulo: $request->titulo,
			anioPublicacion: $request->anioPublicacion,
			tipoDocumentoId: $request->tipoDocumentoId,
			idioma: $request->idioma
		);

		$created = $this->repository->insertArticulo($articulo);

		return ArticuloMapper::toArticuloResponse($created);
	}


	public function updateArticulo(int $id, ArticuloRequest $request): ArticuloResponse
	{
		$existing = $this->repository->findById($id);

		if ($existing === null) {
			throw new ArticuloNotFoundException($id);
		}

		$articulo = Articulo::create(
			titulo: $request->titulo,
			anioPublicacion: $request->anioPublicacion,
			tipoDocumentoId: $request->tipoDocumentoId,
			idioma: $request->idioma
		);

		$updated = $this->repository->updateArticulo($id, $articulo);

		return ArticuloMapper::toArticuloResponse($updated);
	}


	public function deleteArticulo(int $id): void
	{
		if ($this->repository->findById($id) === null) {
			throw new ArticuloNotFoundException($id);
		}

		$this->repository->delete($id);
	}

	
}
