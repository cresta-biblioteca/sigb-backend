<?php

declare(strict_types=1);

namespace App\Auth\Repositories;

use App\Auth\Models\User;
use App\Shared\Repository;

class AuthRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'user';
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    public function findByDni(string $dni): ?User
    {
        $sql = "SELECT * FROM user WHERE dni = :dni LIMIT 1";
        return $this->findOneByQuery($sql, ['dni' => $dni]);
    }

    public function create(array $params): ?User
    {
        $sql = "INSERT INTO user (dni, password, role_id) VALUES (:dni, :password, :role_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return User::fromDatabase($row);
    }
}
