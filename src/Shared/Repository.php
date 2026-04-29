<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Database\Connection;
use App\Shared\Exceptions\NotFoundException;
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
     * Busca una entidad por su ID (excluye soft-deleted si aplica)
     */
    public function findById(int $id): ?Entity
    {
        $filter = $this->usesSoftDelete() ? ' AND deleted_at IS NULL' : '';
        $sql = sprintf(
            'SELECT * FROM %s WHERE id = :id%s LIMIT 1',
            $this->getTableName(),
            $filter
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
     * Mensaje de error cuando la entidad no es encontrada.
     * Las subclases pueden sobreescribir para proveer un mensaje específico.
     */
    protected function getNotFoundMessage(): string
    {
        $parts = explode('\\', $this->getEntityClass());
        return end($parts) . ' no encontrado';
    }

    /**
     * Busca una entidad por ID o lanza excepción
     *
     * @throws NotFoundException
     */
    public function findByIdOrFail(int $id): Entity
    {
        $entity = $this->findById($id);

        if ($entity === null) {
            throw new NotFoundException($this->getNotFoundMessage());
        }

        return $entity;
    }

    /**
     * Obtiene todas las entidades activas (excluye soft-deleted si aplica)
     *
     * @return Entity[]
     */
    public function findAll(): array
    {
        $filter = $this->usesSoftDelete() ? ' WHERE deleted_at IS NULL' : '';
        $sql = sprintf('SELECT * FROM %s%s', $this->getTableName(), $filter);
        $stmt = $this->pdo->query($sql);

        $entityClass = $this->getEntityClass();
        $entities = [];

        while ($row = $stmt->fetch()) {
            $entities[] = $entityClass::fromDatabase($row);
        }

        return $entities;
    }

    /**
     * Indica si esta tabla usa soft delete (deleted_at).
     * Las subclases que tengan deleted_at deben sobreescribir a true.
     */
    protected function usesSoftDelete(): bool
    {
        return false;
    }

    /**
     * Elimina una entidad por su ID (hard delete para tablas sin soft delete)
     */
    public function delete(int $id): bool
    {
        $sql = sprintf('DELETE FROM %s WHERE id = :id', $this->getTableName());
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Soft delete: setea deleted_at en lugar de borrar la fila
     */
    public function softDelete(int $id): bool
    {
        $sql = sprintf(
            'UPDATE %s SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            $this->getTableName()
        );
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
