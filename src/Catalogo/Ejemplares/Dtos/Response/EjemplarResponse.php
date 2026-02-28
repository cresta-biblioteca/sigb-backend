<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Response;


readonly class EjemplarResponse implements \JsonSerializable
{
	public function __construct(
		public int $id,
		public string $codigoBarras,
		public bool $habilitado,
		public int $articuloId,
	) {
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'codigo_barras' => $this->codigoBarras,
			'habilitado' => $this->habilitado,
			'articulo_id' => $this->articuloId,
		];
	}
}
