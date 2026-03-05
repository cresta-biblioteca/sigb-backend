<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Repository;

use App\Catalogo\Articulos\Models\TipoDocumento;
use App\Shared\Repository;
use Exception;

class TipoDocumentoRepository extends Repository
{
	protected function getTableName(): string
	{
		return 'tipo_documento';
	}

	protected function getEntityClass(): string
	{
		return TipoDocumento::class;
	}

	public function insertTipoDocumento(TipoDocumento $doc): TipoDocumento
	{
		$sql = "INSERT INTO tipo_documento(codigo, descripcion, renovable, detalle) VALUES(:codigo, :descripcion, :renovable, :detalle)";
		$params = [
			"codigo" => $doc->getCodigo(),
			"descripcion" => $doc->getDescripcion(),
			"renovable" => (int) $doc->isRenovable(),
			"detalle" => $doc->getDetalle(),
		];
		$stmt = $this->pdo->prepare($sql);
		$success = $stmt->execute($params);

		if ($success === false || $stmt->rowCount() === 0) {
			throw new Exception("Error al insertar el tipo de documento");
		}
		$id = (int) $this->pdo->lastInsertId();

		return $this->findById($id);
	}

	public function updateTipoDocumento(int $id, TipoDocumento $tipoDoc): TipoDocumento {
		$sql = "UPDATE tipo_documento SET codigo = :codigo, descripcion = :descripcion, renovable = :renovable, detalle = :detalle 
				WHERE id = :id";
		$stmt = $this->pdo->prepare($sql);
		$success = $stmt->execute([
			"codigo" => $tipoDoc->getCodigo(),
			"descripcion" => $tipoDoc->getDescripcion(),
			"renovable" => (int) $tipoDoc->isRenovable(),
			"detalle" => $tipoDoc->getDetalle(),
			"id"=> $id
		]);

		if($success === false) {
			throw new Exception("Error al actualizar el documento");
		}

		return $this->findById($id);
	}

	public function findCoincidence(string $codigo, string $descripcion, ?int $excludeId = null): ?TipoDocumento
	{
		$sql = "SELECT * FROM tipo_documento WHERE (codigo = :codigo OR descripcion = :descripcion)";
		$params = [
			"codigo" => $codigo,
			"descripcion" => $descripcion
		];

		if ($excludeId !== null) {
			$sql .= " AND id != :excludeId";
			$params["excludeId"] = $excludeId;
		}
		/** @var ?TipoDocumento */
		return $this->findOneByQuery($sql, $params);
	}

	/**
	 * Busca tipos de documento filtrando por código, descripción, detalle y/o renovable
	 *
	 * @param array{codigo?: string, descripcion?: string, detalle?: string, renovable?: bool, order?: string} $params
	 * @return TipoDocumento[]
	 */
	public function findByParams(array $params): array
	{
		$conditions = [];
		$bindings = [];

		if (!empty($params['codigo'])) {
			$conditions[] = 'codigo LIKE :codigo';
			$escapedCodigo = addcslashes(trim($params['codigo']), '%_');
			$bindings['codigo'] = '%' . $escapedCodigo . '%';
		}
		if (!empty($params['descripcion'])) {
			$conditions[] = 'descripcion LIKE :descripcion';
			$escapedDescripcion = addcslashes(trim($params['descripcion']), '%_');
			$bindings['descripcion'] = '%' . $escapedDescripcion . '%';
		}
		if (!empty($params['detalle'])) {
			$conditions[] = 'detalle LIKE :detalle';
			$escapedDetalle = addcslashes(trim($params['detalle']), '%_');
			$bindings['detalle'] = '%' . $escapedDetalle . '%';
		}
		if (isset($params['renovable'])) {
			$conditions[] = 'renovable = :renovable';
			$bindings['renovable'] = (int) filter_var($params['renovable'], FILTER_VALIDATE_BOOLEAN);
		}

		$sql = sprintf('SELECT * FROM %s', $this->getTableName());

		if (!empty($conditions)) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}
		if (!empty($params['order'])) {
			$order = strtoupper($params['order']) === 'DESC' ? 'DESC' : 'ASC';
			$sql .= " ORDER BY codigo {$order}";
		} else {
			$sql .= ' ORDER BY codigo';
		}

		/** @var TipoDocumento[] */
		return $this->findByQuery($sql, $bindings);
	}
}