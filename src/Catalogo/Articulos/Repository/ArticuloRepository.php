<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Repository;

use App\Catalogo\Articulos\Models\Articulo;
use App\Shared\Repository;

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
        $sql = 'INSERT INTO articulo (titulo, anio_publicacion, tipo_documento_id, idioma, created_at, updated_at)
				VALUES (:titulo, :anio_publicacion, :tipo_documento_id, :idioma, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $articulo->getTitulo(),
            'anio_publicacion' => $articulo->getAnioPublicacion(),
            'tipo_documento_id' => $articulo->getTipoDocumentoId(),
            'idioma' => $articulo->getIdioma(),
        ]);

        if ($success === false || $stmt->rowCount() === 0) {
            throw new \RuntimeException('Error al insertar el artículo');
        }

        $articulo->setId((int) $this->pdo->lastInsertId());

        return $articulo;
    }

    public function updateArticulo(int $id, Articulo $articulo): Articulo
    {
        $sql = 'UPDATE articulo
				SET titulo = :titulo,
					anio_publicacion = :anio_publicacion,
					tipo_documento_id = :tipo_documento_id,
					idioma = :idioma,
					updated_at = NOW()
				WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $articulo->getTitulo(),
            'anio_publicacion' => $articulo->getAnioPublicacion(),
            'tipo_documento_id' => $articulo->getTipoDocumentoId(),
            'idioma' => $articulo->getIdioma(),
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

        return (int) $stmt->fetchColumn() > 0;
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

            if ((int) $stmt->fetchColumn() > 0) {
                return $label;
            }
        }

        return null;
    }
}
