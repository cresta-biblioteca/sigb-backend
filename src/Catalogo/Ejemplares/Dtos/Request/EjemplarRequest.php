<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Request;

class EjemplarRequest
{
	public function __construct(
		public readonly int $articuloId,
		public readonly ?string $codigoBarras = null,
		public readonly bool $habilitado = true
	) {
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromArray(array $data): self
	{
		return new self(
			articuloId: (int) $data['articulo_id'],
			codigoBarras: isset($data['codigo_barras']) ? (string) $data['codigo_barras'] : null,
			habilitado: isset($data['habilitado']) ? (bool) $data['habilitado'] : true
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'articulo_id' => $this->articuloId,
			'codigo_barras' => $this->codigoBarras,
			'habilitado' => $this->habilitado,
		];
	}
}
