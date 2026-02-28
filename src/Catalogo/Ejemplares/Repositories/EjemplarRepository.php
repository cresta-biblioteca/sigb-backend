<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Repositories;

use App\Catalogo\Ejemplares\Models\Ejemplar;
use App\Shared\Repository;

class EjemplarRepository extends Repository
{
	protected function getTableName(): string
	{
		return 'ejemplar';
	}

	protected function getEntityClass(): string
	{
		return Ejemplar::class;
	}

	public function insertEjemplar(Ejemplar $ejemplar): Ejemplar
	{
		$sql = 'INSERT INTO ejemplar (codigo_barras, habilitado, articulo_id, created_at, updated_at)
				VALUES (:codigo_barras, :habilitado, :articulo_id, NOW(), NOW())';

		$stmt = $this->pdo->prepare($sql);
		$success = $stmt->execute([
			'codigo_barras' => $ejemplar->getCodigoBarras(),
			'habilitado' => $ejemplar->isHabilitado() ? 1 : 0,
			'articulo_id' => $ejemplar->getArticuloId(),
		]);

		if ($success === false || $stmt->rowCount() === 0) {
			throw new \RuntimeException('Error al insertar el ejemplar');
		}

		$ejemplar->setId((int) $this->pdo->lastInsertId());

		return $ejemplar;
	}

	public function updateEjemplar(Ejemplar $ejemplar): bool
	{
		$sql = 'UPDATE ejemplar
				SET codigo_barras = :codigo_barras,
					habilitado = :habilitado,
					articulo_id = :articulo_id,
					updated_at = NOW()
				WHERE id = :id';

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			'codigo_barras' => $ejemplar->getCodigoBarras(),
			'habilitado' => $ejemplar->isHabilitado() ? 1 : 0,
			'articulo_id' => $ejemplar->getArticuloId(),
			'id' => $ejemplar->getId(),
		]);

		return $stmt->rowCount() > 0;
	}

	public function findEjemplarByCodigoBarras(string $codigoBarras): ?Ejemplar
	{
		$sql = 'SELECT * FROM ejemplar WHERE codigo_barras = :codigo_barras LIMIT 1';

		/** @var ?Ejemplar */
		return $this->findOneByQuery($sql, [
			'codigo_barras' => $codigoBarras,
		]);
	}
	public function findEjemplaresByArticuloId(int $articuloId): array
	{
		$sql = 'SELECT * FROM ejemplar WHERE articulo_id = :articulo_id ORDER BY id DESC';

		/** @var Ejemplar[] */
		return $this->findByQuery($sql, [
			'articulo_id' => $articuloId,
		]);
	}

	public function findEjemplaresByHabilitado(bool $habilitado): array
	{
		$sql = 'SELECT * FROM ejemplar WHERE habilitado = :habilitado ORDER BY id DESC';

		/** @var Ejemplar[] */
		return $this->findByQuery($sql, [
			'habilitado' => $habilitado ? 1 : 0,
		]);
	}

	public function findEjemplaresHabilitadosByArticuloId(int $articuloId): array
	{
		$sql = 'SELECT * FROM ejemplar
				WHERE articulo_id = :articulo_id
				AND habilitado = 1
				ORDER BY id DESC';

		/** @var Ejemplar[] */
		return $this->findByQuery($sql, [
			'articulo_id' => $articuloId,
		]);
	}

	public function existsEjemplarByCodigoBarras(string $codigoBarras, ?int $excludeId = null): bool
	{
		$sql = 'SELECT COUNT(*) FROM ejemplar WHERE codigo_barras = :codigo_barras';
		$params = ['codigo_barras' => $codigoBarras];

		if ($excludeId !== null) {
			$sql .= ' AND id <> :exclude_id';
			$params['exclude_id'] = $excludeId;
		}

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);

		return (int) $stmt->fetchColumn() > 0;
	}
}
