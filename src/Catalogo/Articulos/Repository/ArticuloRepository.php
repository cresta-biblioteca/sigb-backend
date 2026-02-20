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

		$sql = 'SELECT * FROM articulo';

		if (!empty($conditions)) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}

		$sql .= ' ORDER BY titulo';

		return $this->findByQuery($sql, $params);
	}
}
