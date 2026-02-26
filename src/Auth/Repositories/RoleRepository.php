<?php

declare(strict_types=1);

namespace App\Auth\Repositories;

use App\Shared\Repository;
use App\Auth\Models\Role;

class RoleRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'role';
    }

    protected function getEntityClass(): string
    {
        return Role::class;
    }

    public function getRoleByName(string $name): ?Role
    {
        $name = strtolower($name);
        $sql = 'SELECT * FROM role WHERE nombre = :nombre LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['nombre' => $name]);

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return Role::fromDatabase($row);
    }
}
