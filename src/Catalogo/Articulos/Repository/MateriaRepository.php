<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Repository;

use App\Catalogo\Articulos\Models\Materia;
use App\Shared\Entity;
use App\Shared\Repository;

class MateriaRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'materia';
    }

    /**
     * @return class-string<Materia>
     */
    protected function getEntityClass(): string
    {
        return Materia::class;
    }

    public function insertMateria(Materia $materia): Materia
    {
        $sql = 'INSERT INTO materia(titulo) VALUES (:titulo)';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $materia->getTitulo(),
        ]);

        if ($success === false || $stmt->rowCount() == 0) {
            throw new \Exception("Error al insertar la materia");
        }

        $materia->setId((int) $this->pdo->lastInsertId());
        return $materia;
    }

    public function updateMateria(int $id, Materia $materia): Materia
    {
        $sql = 'UPDATE materia SET titulo = :titulo WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'titulo' => $materia->getTitulo(),
            'id' => $id
        ]);

        if ($success === false) {
            throw new \Exception("Error al actualizar la materia");
        }

        $materia->setId($id);

        return $materia;
    }

    /**
     * Busca materias por título (búsqueda parcial)
     *
     * @return Materia[]
     */
    public function findByTitulo(string $titulo): array
    {
        $sql = 'SELECT * FROM materia WHERE titulo LIKE :titulo ORDER BY titulo';
        $titulo = addcslashes($titulo, '%_');

        return $this->findByQuery($sql, [
            'titulo' => '%' . $titulo . '%',
        ]);
    }

    /**
     * Busca una materia por título exacto
     */
    public function findCoincidence(string $titulo): ?Materia
    {
        $sql = 'SELECT * FROM materia WHERE titulo = :titulo LIMIT 1';

        /** @var ?Materia */
        return $this->findOneByQuery($sql, ['titulo' => $titulo]);
    }
}
