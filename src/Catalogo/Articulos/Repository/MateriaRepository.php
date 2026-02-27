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
    public function findByParams(array $params): array
    {
        $conditions = [];
        $bindings = [];

        if (!empty($params['titulo'])) {
            $conditions[] = 'titulo LIKE :titulo';
            $escapedTitulo = addcslashes(trim($params['titulo']), '%_');
            $bindings['titulo'] = '%' . $escapedTitulo . '%';
        }

        $sql = sprintf('SELECT * FROM %s', $this->getTableName());

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if (!empty($params['order'])) {
            $order = strtoupper($params['order']) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY titulo {$order}";
        } else {
            $sql .= ' ORDER BY titulo';
        }

        /** @var Materia[] */
        return $this->findByQuery($sql, $bindings);
    }

    public function findMateriasByCarrera(int $idCarrera): array
    {
        $sql = "SELECT m.id, m.titulo FROM carrera_materia cm 
                JOIN materia m ON cm.materia_id = m.id
                WHERE cm.carrera_id = :idCarrera
                ORDER BY m.titulo ASC";
        $materias = $this->findByQuery($sql, ["idCarrera" => $idCarrera]);
        return $materias;
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
