<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Repositories;

use App\Catalogo\Libros\Models\Persona;
use App\Shared\Repository;

class PersonaRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'persona';
    }

    protected function getEntityClass(): string
    {
        return Persona::class;
    }

    public function findByNombreApellido(string $nombre, string $apellido): ?Persona
    {
        $sql = "SELECT * FROM persona WHERE nombre = :nombre AND apellido = :apellido LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['nombre' => $nombre, 'apellido' => $apellido]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return Persona::fromDatabase($row);
    }

    public function insertPersona(Persona $persona): Persona
    {
        $sql = "INSERT INTO persona (nombre, apellido, created_at, updated_at)
                VALUES (:nombre, :apellido, NOW(), NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $persona->getNombre(),
            'apellido' => $persona->getApellido(),
        ]);

        $persona->setId((int) $this->pdo->lastInsertId());

        return $persona;
    }

    public function findOrCreate(string $nombre, string $apellido): Persona
    {
        $existing = $this->findByNombreApellido($nombre, $apellido);

        if ($existing !== null) {
            return $existing;
        }

        $persona = Persona::create($nombre, $apellido);

        return $this->insertPersona($persona);
    }
}
