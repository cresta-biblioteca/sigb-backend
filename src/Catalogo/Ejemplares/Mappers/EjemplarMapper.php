<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Mappers;

use App\Catalogo\Ejemplares\Dtos\Request\EjemplarRequest;
use App\Catalogo\Ejemplares\Dtos\Response\EjemplarResponse;
use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Shared\Exceptions\ValidationException;

class EjemplarMapper
{
	/**
	 * @param array<string, mixed> $input
	 */
	public static function fromArray(array $input): EjemplarRequest
	{
		if (!isset($input['articulo_id'])) {
			throw ValidationException::forField('articulo_id', 'El campo articulo_id es obligatorio');
		}

		return new EjemplarRequest(
			articuloId: (int) $input['articulo_id'],
			codigoBarras: isset($input['codigo_barras']) ? trim((string) $input['codigo_barras']) : null,
			habilitado: isset($input['habilitado']) ? (bool) $input['habilitado'] : true
		);
	}

	public static function fromEjemplarRequest(EjemplarRequest $request): Ejemplar
	{
		return Ejemplar::create(
			articuloId: $request->articuloId,
			codigoBarras: $request->codigoBarras,
			habilitado: $request->habilitado
		);
	}

	public static function updateFromRequest(Ejemplar $ejemplar, EjemplarRequest $request): Ejemplar
	{
		$ejemplar->setArticuloId($request->articuloId);
		$ejemplar->setCodigoBarras($request->codigoBarras);
		$ejemplar->setHabilitado($request->habilitado);

		return $ejemplar;
	}

	public static function toEjemplarResponse(Ejemplar $ejemplar): EjemplarResponse
	{
		return new EjemplarResponse(
			id: $ejemplar->getId() ?? 0,
			codigoBarras: $ejemplar->getCodigoBarras(),
			habilitado: $ejemplar->isHabilitado(),
			articuloId: $ejemplar->getArticuloId(),
			createdAt: $ejemplar->getCreatedAt() ?? new \DateTimeImmutable(),
			updatedAt: $ejemplar->getUpdatedAt() ?? new \DateTimeImmutable(),
			articulo: $ejemplar->getArticulo()?->toArray()
		);
	}
}
