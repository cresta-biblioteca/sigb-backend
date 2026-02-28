<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

use JsonSerializable;

readonly class ArticuloResponse implements JsonSerializable
{
	public function __construct(
		public int $id,
		public string $titulo,
		public int $anioPublicacion,
		public int $tipoDocumentoId,
		public string $idioma,
		public ?array $tipoDocumento = null,
		public array $temas = [],
		public array $materias = []
	) {
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'titulo' => $this->titulo,
			'anio_publicacion' => $this->anioPublicacion,
			'tipo_documento_id' => $this->tipoDocumentoId,
			'idioma' => $this->idioma,
			'tipo_documento' => $this->tipoDocumento,
			'temas' => $this->temas,
			'materias' => $this->materias,
		];
	}
}
