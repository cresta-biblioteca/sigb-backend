<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Response;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class EjemplarResponse
{
	/**
	 * @param array<string, mixed>|null $articulo
	 */
	public function __construct(
		public readonly int $id,
		public readonly ?string $codigoBarras,
		public readonly bool $habilitado,
		public readonly int $articuloId,
		public readonly DateTimeImmutable $createdAt,
		public readonly DateTimeImmutable $updatedAt,
		public readonly ?array $articulo = null
	) {
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromArray(array $data): self
	{
		return new self(
			id: (int) $data['id'],
			codigoBarras: isset($data['codigo_barras']) ? (string) $data['codigo_barras'] : null,
			habilitado: (bool) $data['habilitado'],
			articuloId: (int) $data['articulo_id'],
			createdAt: self::parseDateTime($data['created_at'] ?? null, 'created_at'),
			updatedAt: self::parseDateTime($data['updated_at'] ?? null, 'updated_at'),
			articulo: isset($data['articulo']) && is_array($data['articulo']) ? $data['articulo'] : null
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'codigo_barras' => $this->codigoBarras,
			'habilitado' => $this->habilitado,
			'articulo_id' => $this->articuloId,
			'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
			'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
			'articulo' => $this->articulo,
		];
	}

	private static function parseDateTime(mixed $value, string $field): DateTimeImmutable
	{
		if ($value instanceof DateTimeImmutable) {
			return $value;
		}

		if ($value instanceof DateTimeInterface) {
			return DateTimeImmutable::createFromInterface($value);
		}

		if (is_string($value) && $value !== '') {
			return new DateTimeImmutable($value);
		}

		throw new InvalidArgumentException(sprintf('El campo "%s" es requerido y debe ser una fecha válida', $field));
	}
}
