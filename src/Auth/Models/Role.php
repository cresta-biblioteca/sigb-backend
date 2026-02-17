<?php

declare(strict_types=1);

namespace App\Auth\Models;

use App\Shared\Entity;

class Role extends Entity
{
    private string $nombre;
    private string $descripcion;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Role (valida datos)
     */
    public static function create(string $nombre, string $descripcion): self
    {
        $role = new self();
        $role->setNombre($nombre);
        $role->setDescripcion($descripcion);

        return $role;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $role = new self();
        $role->id = (int) $row['id'];
        $role->nombre = $row['nombre'];
        $role->descripcion = $row['descripcion'];
        $role->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );

        return $role;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->assertNotEmpty($nombre, 'nombre');
        $this->assertMaxLength($nombre, 100, 'nombre');
        $this->nombre = $nombre;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->assertNotEmpty($descripcion, 'descripcion');
        $this->assertMaxLength($descripcion, 255, 'descripcion');
        $this->descripcion = $descripcion;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
