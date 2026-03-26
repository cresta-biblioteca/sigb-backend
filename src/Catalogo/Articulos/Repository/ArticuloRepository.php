<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Repository;

use App\Catalogo\Articulos\Exceptions\MateriaAlreadyEliminatedException;
use App\Catalogo\Articulos\Exceptions\MateriaAlreadyInArticuloException;
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

    public function insertArticulo(Articulo $articulo): Articulo
    {
        $sql = 'INSERT INTO articulo
				(titulo, anio_publicacion, tipo_documento_id, idioma, descripcion, created_at, updated_at)
				VALUES (:titulo, :anio_publicacion, :tipo_documento_id, :idioma, :descripcion, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $articulo->getTitulo(),
            'anio_publicacion' => $articulo->getAnioPublicacion(),
            'tipo_documento_id' => $articulo->getTipoDocumentoId(),
            'idioma' => $articulo->getIdioma(),
            'descripcion' => $articulo->getDescripcion(),
        ]);

        if ($success === false || $stmt->rowCount() === 0) {
            throw new \RuntimeException('Error al insertar el artículo');
        }

        $articulo->setId((int)$this->pdo->lastInsertId());

        return $articulo;
    }

    public function updateArticulo(int $id, Articulo $articulo): Articulo
    {
        $sql = 'UPDATE articulo
				SET titulo = :titulo,
					anio_publicacion = :anio_publicacion,
					tipo_documento_id = :tipo_documento_id,
					idioma = :idioma,
					descripcion = :descripcion,
					updated_at = NOW()
				WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $articulo->getTitulo(),
            'anio_publicacion' => $articulo->getAnioPublicacion(),
            'tipo_documento_id' => $articulo->getTipoDocumentoId(),
            'idioma' => $articulo->getIdioma(),
            'descripcion' => $articulo->getDescripcion(),
            'id' => $id,
        ]);

        if ($success === false) {
            throw new \RuntimeException('Error al actualizar el artículo');
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

    public function isLinkedToLibro(int $articuloId): bool
    {
        $sql = 'SELECT COUNT(*) FROM libro WHERE articulo_id = :articulo_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['articulo_id' => $articuloId]);

        return (int)$stmt->fetchColumn() > 0;
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
    public function materiaExists(int $materiaId): bool
    {
        $sql = 'SELECT 1 FROM materia WHERE id = :materia_id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['materia_id' => $materiaId]);

        return $stmt->fetch() !== false;
    }

    public function isMateriaAdded(int $articuloId, int $materiaId): bool
    {
        $sql = 'SELECT 1 FROM materia_articulo WHERE articulo_id = :articulo_id AND materia_id = :materia_id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'articulo_id' => $articuloId,
            'materia_id' => $materiaId,
        ]);

        return $stmt->fetch() !== false;
    }

    public function addMateriaToArticulo(int $articuloId, int $materiaId): void
    {
        $sql = 'INSERT INTO materia_articulo (articulo_id, materia_id) VALUES (:articulo_id, :materia_id)';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                'articulo_id' => $articuloId,
                'materia_id' => $materiaId,
            ]);
        } catch (\PDOException $e) {
            if ($this->isMateriaAlreadyInArticuloViolation($e)) {
                throw new MateriaAlreadyInArticuloException();
            }

            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Error al agregar la materia al artículo');
        }
    }

    private function isMateriaAlreadyInArticuloViolation(\PDOException $exception): bool
    {
        if ($exception->getCode() !== '23000') {
            return false;
        }

        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        if ($driverCode !== 1062) {
            return false;
        }

        $details = strtolower((string) ($exception->errorInfo[2] ?? $exception->getMessage()));

        return str_contains($details, 'materia_articulo') || str_contains($details, 'primary');
    }

    public function deleteMateriaFromArticulo(int $articuloId, int $materiaId): void
    {
        $sql = 'DELETE FROM materia_articulo WHERE articulo_id = :articulo_id AND materia_id = :materia_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'articulo_id' => $articuloId,
            'materia_id' => $materiaId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new MateriaAlreadyEliminatedException();
        }
    }

    /**
     * @return string[]
     */
    public function findMateriaTitlesByArticuloId(int $articuloId): array
    {
        $sql = 'SELECT m.titulo
                FROM materia_articulo ma
                INNER JOIN materia m ON m.id = ma.materia_id
                WHERE ma.articulo_id = :articulo_id
                ORDER BY m.titulo ASC';

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
            'materia_articulo' => 'materia asociada',
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
