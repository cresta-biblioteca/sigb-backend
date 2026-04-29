<?php

declare(strict_types=1);

namespace App\Lectores\Repositories;

use App\Lectores\Models\Carrera;
use App\Shared\Entity;
use App\Shared\Repository;
use Exception;

class CarreraRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'carrera';
    }

    protected function getEntityClass(): string
    {
        return Carrera::class;
    }

    protected function usesSoftDelete(): bool
    {
        return true;
    }

    /**
     * Inserta una nueva carrera en la base de datos
     *
     * @throws Exception
     */
    public function insertCarrera(Carrera $carrera): Carrera
    {
        $sql = 'INSERT INTO carrera (codigo, nombre) VALUES (:codigo, :nombre)';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'codigo' => $carrera->getCodigo(),
            'nombre' => $carrera->getNombre(),
        ]);

        if ($success === false || $stmt->rowCount() === 0) {
            throw new Exception('Error al insertar la carrera');
        }

        $carrera->setId((int) $this->pdo->lastInsertId());

        return $carrera;
    }

    /**
     * Actualiza una carrera existente
     *
     * @throws Exception
     */
    public function updateCarrera(int $id, Carrera $carrera): Carrera
    {
        $sql = 'UPDATE carrera SET codigo = :codigo, nombre = :nombre WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'codigo' => $carrera->getCodigo(),
            'nombre' => $carrera->getNombre(),
            'id' => $id,
        ]);

        if ($success === false) {
            throw new Exception('Error al actualizar la carrera');
        }

        $carrera->setId($id);

        return $carrera;
    }

    /**
     * Busca una carrera por su código exacto
     */
    public function findByCodigo(string $codigo): ?Carrera
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE codigo = :codigo AND deleted_at IS NULL LIMIT 1',
            $this->getTableName()
        );

        /** @var ?Carrera */
        return $this->findOneByQuery($sql, ['codigo' => strtoupper($codigo)]);
    }

    /**
     * Busca carreras cuyo nombre contenga el texto dado
     *
     * @return Carrera[]
     */
    public function findByNombre(string $nombre): array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE nombre LIKE :nombre AND deleted_at IS NULL',
            $this->getTableName()
        );
        $nombre = addcslashes($nombre, '%_');

        /** @var Carrera[] */
        return $this->findByQuery($sql, ['nombre' => '%' . $nombre . '%']);
    }

    /**
     * Busca carreras filtrando por código (exacto) y/o nombre (parcial)
     *
     * @param array{cod?: string, nombre?: string} $params
     * @return Carrera[]
     */
    public function findByParams(array $params): array
    {
        $conditions = [];
        $bindings = [];

        if (!empty($params['cod'])) {
            $conditions[] = 'codigo = :codigo';
            $bindings['codigo'] = strtoupper($params['cod']);
        }

        if (!empty($params['nombre'])) {
            $conditions[] = 'nombre LIKE :nombre';
            $escapedNombre = addcslashes($params['nombre'], '%_');
            $bindings['nombre'] = '%' . $escapedNombre . '%';
        }

        $conditions[] = 'deleted_at IS NULL';
        $sql = sprintf('SELECT * FROM %s WHERE %s', $this->getTableName(), implode(' AND ', $conditions));

        if (!empty($params["order"])) {
            $order = strtoupper($params['order']) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY nombre {$order}";
        } else {
            $sql .= ' ORDER BY nombre';
        }

        /** @var Carrera[] */
        return $this->findByQuery($sql, $bindings);
    }

    /**
     * Verifica si existe una carrera con el código dado
     */
    public function existsByCodigo(string $codigo): bool
    {
        $sql = sprintf(
            'SELECT 1 FROM %s WHERE codigo = :codigo AND deleted_at IS NULL LIMIT 1',
            $this->getTableName()
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['codigo' => strtoupper($codigo)]);

        return $stmt->fetch() !== false;
    }

    public function findCoincidence(string $cod, string $nombre): ?Carrera
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE (codigo = :cod OR nombre = :nombre) AND deleted_at IS NULL LIMIT 1',
            $this->getTableName()
        );

        /** @var ?Carrera */
        return $this->findOneByQuery($sql, [
            'cod' => strtoupper($cod),
            'nombre' => $nombre,
        ]);
    }
}
