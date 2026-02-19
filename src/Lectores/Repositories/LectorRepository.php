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

    public function create(array $params) : ?Lector
    {
        $sql = "INSERT INTO {$this->getTableName()} (nombre, apellido, email) VALUES (:nombre, :apellido, :email)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch();

        if ($row === false)
        {
            return null;
        }

        return Lector::fromDatabase($row);
    }

    public function existsByEmail(string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }
}