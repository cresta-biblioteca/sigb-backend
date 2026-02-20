<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Mappers;

use App\Catalogo\Articulos\Dtos\Request\ArticuloRequest;
use App\Catalogo\Articulos\Dtos\Response\ArticuloResponse;
use App\Catalogo\Articulos\Models\Articulo;
use App\Shared\Exceptions\ValidationException;

class ArticuloMapper
{
	/**
	 * @param array<string, mixed> $input
	 */
	public static function fromArray(array $input): ArticuloRequest
	{
		if (!isset($input['titulo'])) {
			throw ValidationException::forField('titulo', 'El campo titulo es obligatorio');
		}

		if (!isset($input['anio_publicacion'])) {
			throw ValidationException::forField('anio_publicacion', 'El campo anio_publicacion es obligatorio');
		}

		if (!isset($input['tipo_documento_id'])) {
			throw ValidationException::forField('tipo_documento_id', 'El campo tipo_documento_id es obligatorio');
		}

		return new ArticuloRequest(
			titulo: trim((string) $input['titulo']),
			anioPublicacion: (int) $input['anio_publicacion'],
			tipoDocumentoId: (int) $input['tipo_documento_id'],
			idioma: isset($input['idioma']) ? strtolower((string) $input['idioma']) : 'es'
		);
	}

	public static function fromArticuloRequest(ArticuloRequest $request): Articulo
	{
		return Articulo::create(
			titulo: $request->titulo,
			anioPublicacion: $request->anioPublicacion,
			tipoDocumentoId: $request->tipoDocumentoId,
			idioma: $request->idioma
		);
	}

	public static function toArticuloResponse(Articulo $articulo): ArticuloResponse
	{
		return new ArticuloResponse(
			id: $articulo->getId() ?? 0,
			titulo: $articulo->getTitulo(),
			anioPublicacion: $articulo->getAnioPublicacion(),
			tipoDocumentoId: $articulo->getTipoDocumentoId(),
			idioma: $articulo->getIdioma(),
			createdAt: $articulo->getCreatedAt() ?? new \DateTimeImmutable(),
			updatedAt: $articulo->getUpdatedAt() ?? new \DateTimeImmutable(),
			tipoDocumento: $articulo->getTipoDocumento()?->toArray(),
			temas: array_map(
				fn($tema) => $tema->toArray(),
				$articulo->getTemas()
			),
			materias: array_map(
				fn($materia) => $materia->toArray(),
				$articulo->getMaterias()
			)
		);
	}
}
