<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Database\Connection;
use App\Shared\Exceptions\EntityNotFoundException;
use PDO;

abstract class Repository
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    /**
     * Nombre de la tabla en la base de datos
     */
    abstract protected function getTableName(): string;

    /**
     * Clase de entidad que maneja este repositorio
     *
     * @return class-string<Entity>
     */
    abstract protected function getEntityClass(): string;

    /**
     * Busca una entidad por su ID
     */
    public function findById(int $id): ?Entity
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE id = :id LIMIT 1',
            $this->getTableName()
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $entityClass = $this->getEntityClass();
        return $entityClass::fromDatabase($row);
    }

    /**
     * Busca una entidad por ID o lanza excepción
     *
     * @throws EntityNotFoundException
     */
    public function findByIdOrFail(int $id): Entity
    {
        $entity = $this->findById($id);

        if ($entity === null) {
            throw new EntityNotFoundException($this->getEntityClass(), $id);
        }

        return $entity;
    }

    /**
     * Obtiene todas las entidades
     *
     * @return Entity[]
     */
    public function findAll(): array
    {
        $sql = sprintf('SELECT * FROM %s', $this->getTableName());
        $stmt = $this->pdo->query($sql);

        $entityClass = $this->getEntityClass();
        $entities = [];

        while ($row = $stmt->fetch()) {
            $entities[] = $entityClass::fromDatabase($row);
        }

        return $entities;
    }

    /**
     * Elimina una entidad por su ID
     */
    public function delete(int $id): bool
    {
        $sql = sprintf('DELETE FROM %s WHERE id = :id', $this->getTableName());
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Cuenta el total de registros
     */
    public function count(): int
    {
        $sql = sprintf('SELECT COUNT(*) FROM %s', $this->getTableName());
        $stmt = $this->pdo->query($sql);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Verifica si existe una entidad con el ID dado
     */
    public function exists(int $id): bool
    {
        $sql = sprintf(
            'SELECT 1 FROM %s WHERE id = :id LIMIT 1',
            $this->getTableName()
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() !== false;
    }

    /**
     * Ejecuta una consulta con parámetros y retorna las entidades
     *
     * @param array<string, mixed> $params
     * @return Entity[]
     */
    protected function findByQuery(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $entityClass = $this->getEntityClass();
        $entities = [];

        while ($row = $stmt->fetch()) {
            $entities[] = $entityClass::fromDatabase($row);
        }

        return $entities;
    }

    /**
     * Ejecuta una consulta y retorna una sola entidad
     *
     * @param array<string, mixed> $params
     */
    protected function findOneByQuery(string $sql, array $params = []): ?Entity
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $entityClass = $this->getEntityClass();
        return $entityClass::fromDatabase($row);
    }
}
