<?php

declare(strict_types=1);

namespace App\Lectores\Repositories;

use App\Lectores\Models\Carrera;
use App\Lectores\Models\Lector;
use App\Shared\Repository;

class LectorRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'lector';
    }

    protected function getEntityClass(): string
    {
        return Lector::class;
    }

    protected function usesSoftDelete(): bool
    {
        return true;
    }

    public function existsByTarjetaId(string $tarjetaId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE tarjeta_id = :tarjeta_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tarjeta_id' => $tarjetaId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $params): ?Lector
    {
        $sql = "
            INSERT INTO {$this->getTableName()}
                (tarjeta_id, user_id, nombre, apellido, fecha_nacimiento,
                telefono, email, legajo, genero, cresta_id)
            VALUES
                (:tarjeta_id, :user_id, :nombre, :apellido, :fecha_nacimiento,
                :telefono, :email, :legajo, :genero, :cresta_id)
            ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function existsByEmail(string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE email = :email AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function findByUserId(int $userId): ?Lector
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1";
        /** @var ?Lector $lector */
        $lector = $this->findOneByQuery($sql, ['user_id' => $userId]);

        if ($lector === null) {
            return null;
        }

        $lector->setCarreras($this->findCarrerasByLectorId($lector->getId()));

        return $lector;
    }

    /**
     * @return Carrera[]
     */
    public function findCarrerasByLectorId(int $lectorId): array
    {
        $sql = "
            SELECT c.*
            FROM carrera c
            INNER JOIN lector_carrera lc ON lc.carrera_id = c.id
            WHERE lc.lector_id = :lector_id
            ORDER BY c.nombre
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['lector_id' => $lectorId]);

        $carreras = [];
        while ($row = $stmt->fetch()) {
            $carreras[] = Carrera::fromDatabase($row);
        }

        return $carreras;
    }

    public function hasCarrera(int $lectorId, int $carreraId): bool
    {
        $sql = "
            SELECT 1
            FROM lector_carrera
            WHERE lector_id = :lector_id AND carrera_id = :carrera_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'lector_id' => $lectorId,
            'carrera_id' => $carreraId,
        ]);

        return $stmt->fetch() !== false;
    }

    public function assignCarrera(int $lectorId, int $carreraId): bool
    {
        $sql = '
            INSERT INTO lector_carrera (lector_id, carrera_id)
            VALUES (:lector_id, :carrera_id)
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'lector_id' => $lectorId,
            'carrera_id' => $carreraId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function removeCarrera(int $lectorId, int $carreraId): bool
    {
        $sql = '
            DELETE FROM lector_carrera
            WHERE lector_id = :lector_id AND carrera_id = :carrera_id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'lector_id' => $lectorId,
            'carrera_id' => $carreraId,
        ]);

        return $stmt->rowCount() > 0;
    }
}
