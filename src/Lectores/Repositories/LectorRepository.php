<?php

declare(strict_types=1);

namespace App\Lectores\Repositories;

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
        $sql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function findByUserId(int $userId): ?Lector
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE user_id = :user_id LIMIT 1";
        return $this->findOneByQuery($sql, ['user_id' => $userId]);
    }
}
