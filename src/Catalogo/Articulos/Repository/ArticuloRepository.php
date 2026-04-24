<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Repository;

use App\Catalogo\Articulos\Models\Articulo;
use App\Shared\Repository;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\TemaAlreadyInArticuloException;

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

    protected function usesSoftDelete(): bool
    {
        return true;
    }

    public function insertArticulo(Articulo $articulo): Articulo
    {
        $sql = 'INSERT INTO articulo
				(titulo, anio_publicacion, tipo, idioma, descripcion, created_at, updated_at)
				VALUES (:titulo, :anio_publicacion, :tipo, :idioma, :descripcion, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $articulo->getTitulo(),
            'anio_publicacion' => $articulo->getAnioPublicacion(),
            'tipo' => $articulo->getTipo(),
            'idioma' => $articulo->getIdioma(),
            'descripcion' => $articulo->getDescripcion(),
        ]);

        if ($success === false || $stmt->rowCount() === 0) {
            throw new \RuntimeException('Error al insertar el artículo');
        }

        $articulo->setId((int)$this->pdo->lastInsertId());

        return $articulo;
    }

    /**
     * @param array<int, string> $fields Nombres de los campos a actualizar
     */
    public function updateArticulo(int $id, Articulo $articulo, array $fields): Articulo
    {
        $getters = [
            'titulo'           => fn() => $articulo->getTitulo(),
            'anio_publicacion' => fn() => $articulo->getAnioPublicacion(),
            'idioma'           => fn() => $articulo->getIdioma(),
            'descripcion'      => fn() => $articulo->getDescripcion(),
        ];

        $setClauses = [];
        $params = ['id' => $id];

        foreach ($fields as $field) {
            if (!isset($getters[$field])) {
                continue;
            }
            $setClauses[] = "{$field} = :{$field}";
            $params[$field] = $getters[$field]();
        }

        if (!empty($setClauses)) {
            $setClauses[] = 'updated_at = NOW()';
            $sql = 'UPDATE articulo SET ' . implode(', ', $setClauses) . ' WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            if ($stmt->execute($params) === false) {
                throw new \RuntimeException('Error al actualizar el artículo');
            }
        }

        $articulo->setId($id);

        return $articulo;
    }

    /**
     * @return Articulo[]
     */
    public function findByTitulo(string $titulo): array
    {
        $sql = 'SELECT * FROM articulo WHERE titulo LIKE :titulo AND deleted_at IS NULL ORDER BY titulo';

        return $this->findByQuery($sql, [
            'titulo' => '%' . $titulo . '%',
        ]);
    }

    public function temaExists(int $temaId): bool
    {
        $sql = 'SELECT 1 FROM tema WHERE id = :tema_id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tema_id' => $temaId]);

        return $stmt->fetch() !== false;
    }

    public function isTemaAdded(int $articuloId, int $temaId): bool
    {
        $sql = 'SELECT 1 FROM articulo_tema WHERE articulo_id = :articulo_id AND tema_id = :tema_id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'articulo_id' => $articuloId,
            'tema_id' => $temaId,
        ]);

        return $stmt->fetch() !== false;
    }

    public function addTemaToArticulo(int $articuloId, int $temaId): void
    {
        $sql = 'INSERT INTO articulo_tema (articulo_id, tema_id) VALUES (:articulo_id, :tema_id)';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                'articulo_id' => $articuloId,
                'tema_id' => $temaId,
            ]);
        } catch (\PDOException $e) {
            if ($this->isTemaAlreadyInArticuloViolation($e)) {
                throw new TemaAlreadyInArticuloException();
            }
            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Error al agregar el tema al artículo');
        }
    }

    private function isTemaAlreadyInArticuloViolation(\PDOException $exception): bool
    {
        // SQLSTATE 23000 en MySQL cubre múltiples errores de integridad; solo mapeamos clave duplicada (1062).
        if ($exception->getCode() !== '23000') {
            return false;
        }

        $driverCode = (int)($exception->errorInfo[1] ?? 0);
        if ($driverCode !== 1062) {
            return false;
        }

        $details = strtolower((string)($exception->errorInfo[2] ?? $exception->getMessage()));

        return str_contains($details, 'articulo_tema') || str_contains($details, 'primary');
    }

    public function deleteTemaFromArticulo(int $articuloId, int $temaId): void
    {
        $sql = 'DELETE FROM articulo_tema WHERE articulo_id = :articulo_id AND tema_id = :tema_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'articulo_id' => $articuloId,
            'tema_id' => $temaId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new TemaAlreadyEliminatedException();
        }
    }

    /**
     * @return string[]
     */
    public function findTemaTitlesByArticuloId(int $articuloId): array
    {
        $sql = 'SELECT t.titulo
                FROM articulo_tema at
                INNER JOIN tema t ON t.id = at.tema_id
                WHERE at.articulo_id = :articulo_id
                ORDER BY t.titulo ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['articulo_id' => $articuloId]);

        /** @var string[] */
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getDeleteBlockingRelation(int $articuloId): ?string
    {
        $relations = [
            'libro' => 'libro asociado',
            'ejemplar' => 'ejemplar asociado',
            'articulo_tema' => 'tema asociado',
        ];

        foreach ($relations as $table => $label) {
            $sql = "SELECT COUNT(*) FROM {$table} WHERE articulo_id = :articulo_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['articulo_id' => $articuloId]);

            if ((int)$stmt->fetchColumn() > 0) {
                return $label;
            }
        }

        return null;
    }
}
