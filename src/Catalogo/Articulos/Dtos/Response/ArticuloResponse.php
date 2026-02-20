<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Response;

use DateTimeImmutable;

class ArticuloResponse
{
	/**
	 * @param array<string, mixed>|null $tipoDocumento
	 * @param array<int, array<string, mixed>> $temas
	 * @param array<int, array<string, mixed>> $materias
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $titulo,
		public readonly int $anioPublicacion,
		public readonly int $tipoDocumentoId,
		public readonly string $idioma,
		public readonly DateTimeImmutable $createdAt,
		public readonly DateTimeImmutable $updatedAt,
		public readonly ?array $tipoDocumento = null,
		public readonly array $temas = [],
		public readonly array $materias = []
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'titulo' => $this->titulo,
			'anio_publicacion' => $this->anioPublicacion,
			'tipo_documento_id' => $this->tipoDocumentoId,
			'idioma' => $this->idioma,
			'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
			'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
			'tipo_documento' => $this->tipoDocumento,
			'temas' => $this->temas,
			'materias' => $this->materias,
		];
	}
}
