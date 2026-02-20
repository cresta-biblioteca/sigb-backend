<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

class ArticuloRequest
{
	public function __construct(
		public readonly string $titulo,
		public readonly int $anioPublicacion,
		public readonly int $tipoDocumentoId,
		public readonly string $idioma = 'es'
	) {
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromArray(array $data): self
	{
		return new self(
			titulo: (string) $data['titulo'],
			anioPublicacion: (int) $data['anio_publicacion'],
			tipoDocumentoId: (int) $data['tipo_documento_id'],
			idioma: isset($data['idioma']) ? (string) $data['idioma'] : 'es'
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'titulo' => $this->titulo,
			'anio_publicacion' => $this->anioPublicacion,
			'tipo_documento_id' => $this->tipoDocumentoId,
			'idioma' => $this->idioma,
		];
	}
}
