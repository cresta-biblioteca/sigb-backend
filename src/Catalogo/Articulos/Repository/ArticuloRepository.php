<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Repository;

use App\Catalogo\Articulos\Exceptions\ArticuloPersistenceException;
use App\Catalogo\Articulos\Models\Articulo;
use App\Shared\Entity;
use App\Shared\Repository;
use Throwable;

class ArticuloRepository extends Repository
{
	protected function getTableName(): string
	{
		return 'articulo';
	}

	/**
	 * @return class-string<Articulo>
	 */
	protected function getEntityClass(): string
	{
		return Articulo::class;
	}

	public function insertArticulo(Entity $articulo): Articulo
	{
		/** @var Articulo $articulo */
		$sql = 'INSERT INTO articulo (titulo, anio_publicacion, tipo_documento_id, idioma, created_at, updated_at)
				VALUES (:titulo, :anio_publicacion, :tipo_documento_id, :idioma, NOW(), NOW())';

		try {
			$stmt = $this->pdo->prepare($sql);
			$success = $stmt->execute([
				'titulo' => $articulo->getTitulo(),
				'anio_publicacion' => $articulo->getAnioPublicacion(),
				'tipo_documento_id' => $articulo->getTipoDocumentoId(),
				'idioma' => $articulo->getIdioma(),
			]);

			if ($success === false || $stmt->rowCount() === 0) {
				throw ArticuloPersistenceException::databaseError('No se pudo insertar el artículo');
			}
		} catch (Throwable $e) {
			throw ArticuloPersistenceException::databaseError($e->getMessage(), $e);
		}

		$articulo->setId((int) $this->pdo->lastInsertId());

		return $articulo;
	}

	public function updateArticulo(int $id, Entity $articulo): Articulo
	{
		/** @var Articulo $articulo */
		$sql = 'UPDATE articulo
				SET titulo = :titulo,
					anio_publicacion = :anio_publicacion,
					tipo_documento_id = :tipo_documento_id,
					idioma = :idioma,
					updated_at = NOW()
				WHERE id = :id';

		try {
			$stmt = $this->pdo->prepare($sql);
			$success = $stmt->execute([
				'titulo' => $articulo->getTitulo(),
				'anio_publicacion' => $articulo->getAnioPublicacion(),
				'tipo_documento_id' => $articulo->getTipoDocumentoId(),
				'idioma' => $articulo->getIdioma(),
				'id' => $id,
			]);

			if ($success === false) {
				throw ArticuloPersistenceException::databaseError('No se pudo actualizar el artículo');
			}
		} catch (Throwable $e) {
			throw ArticuloPersistenceException::databaseError($e->getMessage(), $e);
		}

		$articulo->setId($id);

		return $articulo;
	}

	/**
	 * @return Articulo[]
	 */
	public function findByTitulo(string $titulo): array
	{
		$sql = 'SELECT * FROM articulo WHERE titulo LIKE :titulo ORDER BY titulo';

		return $this->findByQuery($sql, [
			'titulo' => '%' . $titulo . '%',
		]);
	}

	/**
	 * @return Articulo[]
	 */
	public function findByTipoDocumentoId(int $tipoDocumentoId): array
	{
		$sql = 'SELECT * FROM articulo WHERE tipo_documento_id = :tipo_documento_id ORDER BY titulo';

		return $this->findByQuery($sql, [
			'tipo_documento_id' => $tipoDocumentoId,
		]);
	}

	/**
	 * @return Articulo[]
	 */
	public function findByIdioma(string $idioma): array
	{
		$sql = 'SELECT * FROM articulo WHERE idioma = :idioma ORDER BY titulo';

		return $this->findByQuery($sql, [
			'idioma' => strtolower($idioma),
		]);
	}

	/**
	 * @return Articulo[]
	 */
	public function findByAnioPublicacion(int $anioPublicacion): array
	{
		$sql = 'SELECT * FROM articulo WHERE anio_publicacion = :anio_publicacion ORDER BY titulo';

		return $this->findByQuery($sql, [
			'anio_publicacion' => $anioPublicacion,
		]);
	}

	/**
	 * @return Articulo[]
	 */
	public function findByAnioRange(int $min, int $max): array
	{
		$sql = 'SELECT * FROM articulo
				WHERE anio_publicacion BETWEEN :min AND :max
				ORDER BY anio_publicacion DESC, titulo';

		return $this->findByQuery($sql, [
			'min' => $min,
			'max' => $max,
		]);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return Articulo[]
	 */
	public function search(array $filters): array
	{
		[$conditions, $params] = $this->buildSearchConditionsAndParams($filters);

		$sql = 'SELECT * FROM articulo';

		if (!empty($conditions)) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}

		$sql .= ' ORDER BY titulo';

		return $this->findByQuery($sql, $params);
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return Articulo[]
	 */
	public function searchPaginated(array $filters, int $page, int $perPage): array
	{
		$page = max(1, $page);
		$perPage = max(1, $perPage);
		$offset = ($page - 1) * $perPage;

		[$conditions, $params] = $this->buildSearchConditionsAndParams($filters);

		$sql = 'SELECT * FROM articulo';

		if (!empty($conditions)) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}

		$sql .= ' ORDER BY titulo LIMIT :limit OFFSET :offset';

		$stmt = $this->pdo->prepare($sql);

		foreach ($params as $name => $value) {
			$stmt->bindValue(':' . $name, $value);
		}

		$stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
		$stmt->execute();

		$articulos = [];
		while ($row = $stmt->fetch()) {
			$articulos[] = Articulo::fromDatabase($row);
		}

		return $articulos;
	}

	/**
	 * @param array<string, mixed> $filters
	 */
	public function countSearch(array $filters): int
	{
		[$conditions, $params] = $this->buildSearchConditionsAndParams($filters);

		$sql = 'SELECT COUNT(*) FROM articulo';

		if (!empty($conditions)) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);

		return (int) $stmt->fetchColumn();
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array{0: array<int, string>, 1: array<string, mixed>}
	 */
	private function buildSearchConditionsAndParams(array $filters): array
	{
		$conditions = [];
		$params = [];

		if (!empty($filters['titulo'])) {
			$conditions[] = 'titulo LIKE :titulo';
			$params['titulo'] = '%' . $filters['titulo'] . '%';
		}

		if (!empty($filters['tipo_documento_id'])) {
			$conditions[] = 'tipo_documento_id = :tipo_documento_id';
			$params['tipo_documento_id'] = (int) $filters['tipo_documento_id'];
		}

		if (!empty($filters['idioma'])) {
			$conditions[] = 'idioma = :idioma';
			$params['idioma'] = strtolower((string) $filters['idioma']);
		}

		if (!empty($filters['anio_publicacion'])) {
			$conditions[] = 'anio_publicacion = :anio_publicacion';
			$params['anio_publicacion'] = (int) $filters['anio_publicacion'];
		}

		$temaIds = array_values(array_filter(
			array_map(static fn(mixed $id): int => (int) $id, $this->normalizeFilterAsArray($filters['tema_ids'] ?? null)),
			static fn(int $id): bool => $id > 0
		));

		if ($temaIds !== []) {
			$this->appendExistsByIdsCondition(
				conditions: $conditions,
				params: $params,
				ids: $temaIds,
				prefix: 'tema_id_',
				pivotTable: 'articulo_tema',
				pivotAlias: 'at',
				foreignKey: 'tema_id'
			);
		}

		$materiaIds = array_values(array_filter(
			array_map(static fn(mixed $id): int => (int) $id, $this->normalizeFilterAsArray($filters['materia_ids'] ?? null)),
			static fn(int $id): bool => $id > 0
		));

		if ($materiaIds !== []) {
			$this->appendExistsByIdsCondition(
				conditions: $conditions,
				params: $params,
				ids: $materiaIds,
				prefix: 'materia_id_',
				pivotTable: 'materia_articulo',
				pivotAlias: 'ma',
				foreignKey: 'materia_id'
			);
		}

		$temas = array_values(array_filter(
			array_map(static fn(mixed $tema): string => trim((string) $tema), $this->normalizeFilterAsArray($filters['temas'] ?? null)),
			static fn(string $tema): bool => $tema !== ''
		));

		if ($temas !== []) {
			$this->appendExistsByTitleCondition(
				conditions: $conditions,
				params: $params,
				values: $temas,
				prefix: 'tema_titulo_',
				pivotTable: 'articulo_tema',
				pivotAlias: 'at',
				relatedTable: 'tema',
				relatedAlias: 't',
				relatedIdField: 'tema_id'
			);
		}

		$materias = array_values(array_filter(
			array_map(static fn(mixed $materia): string => trim((string) $materia), $this->normalizeFilterAsArray($filters['materias'] ?? null)),
			static fn(string $materia): bool => $materia !== ''
		));

		if ($materias !== []) {
			$this->appendExistsByTitleCondition(
				conditions: $conditions,
				params: $params,
				values: $materias,
				prefix: 'materia_titulo_',
				pivotTable: 'materia_articulo',
				pivotAlias: 'ma',
				relatedTable: 'materia',
				relatedAlias: 'm',
				relatedIdField: 'materia_id'
			);
		}

		return [$conditions, $params];
	}

	/**
	 * @param array<int, string> $conditions
	 * @param array<string, mixed> $params
	 * @param array<int, int> $ids
	 */
	private function appendExistsByIdsCondition(
		array &$conditions,
		array &$params,
		array $ids,
		string $prefix,
		string $pivotTable,
		string $pivotAlias,
		string $foreignKey
	): void {
		$inParams = [];

		foreach ($ids as $index => $id) {
			$paramName = $prefix . $index;
			$inParams[] = ':' . $paramName;
			$params[$paramName] = $id;
		}

		$conditions[] = 'EXISTS (
			SELECT 1
			FROM ' . $pivotTable . ' ' . $pivotAlias . '
			WHERE ' . $pivotAlias . '.articulo_id = articulo.id
			AND ' . $pivotAlias . '.' . $foreignKey . ' IN (' . implode(', ', $inParams) . ')
		)';
	}

	/**
	 * @param array<int, string> $conditions
	 * @param array<string, mixed> $params
	 * @param array<int, string> $values
	 */
	private function appendExistsByTitleCondition(
		array &$conditions,
		array &$params,
		array $values,
		string $prefix,
		string $pivotTable,
		string $pivotAlias,
		string $relatedTable,
		string $relatedAlias,
		string $relatedIdField
	): void {
		$titleConditions = [];

		foreach ($values as $index => $value) {
			$paramName = $prefix . $index;
			$titleConditions[] = $relatedAlias . '.titulo LIKE :' . $paramName;
			$params[$paramName] = '%' . $value . '%';
		}

		$conditions[] = 'EXISTS (
			SELECT 1
			FROM ' . $pivotTable . ' ' . $pivotAlias . '
			INNER JOIN ' . $relatedTable . ' ' . $relatedAlias . ' ON ' . $relatedAlias . '.id = ' . $pivotAlias . '.' . $relatedIdField . '
			WHERE ' . $pivotAlias . '.articulo_id = articulo.id
			AND (' . implode(' OR ', $titleConditions) . ')
		)';
	}

	/**
	 * @return array<int, mixed>
	 */
	private function normalizeFilterAsArray(mixed $value): array
	{
		if (is_array($value)) {
			return array_values(array_filter(
				$value,
				static fn(mixed $item): bool => $item !== null && $item !== ''
			));
		}

		if ($value === null || $value === '') {
			return [];
		}

		return [$value];
	}
}
