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

    protected function insert(Entity $entity): void
    {
        /** @var Materia $entity */
        $sql = 'INSERT INTO materia(titulo) VALUES (:titulo)';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'titulo' => $entity->getTitulo(),
        ]);

        $entity->setId((int) $this->pdo->lastInsertId());
    }

    protected function update(Entity $entity): void
    {
        /** @var Materia $entity */
        $sql = 'UPDATE materia SET titulo = :titulo WHERE id = :id';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'titulo' => $entity->getTitulo(),
            'id' => $entity->getId(),
        ]);
    }

    /**
     * Busca materias por título (búsqueda parcial)
     *
     * @return Materia[]
     */
    public function findByTitulo(string $titulo): array
    {
        $sql = 'SELECT * FROM materia WHERE titulo LIKE :titulo ORDER BY titulo';
        
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