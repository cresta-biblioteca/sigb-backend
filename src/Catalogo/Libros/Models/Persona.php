<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Models;

use App\Shared\Entity;

class Persona extends Entity
{
    private string $nombre;
    private string $apellido;

    private function __construct()
    {
    }

    public static function create(string $nombre, string $apellido): self
    {
        $persona = new self();
        $persona->setNombre($nombre);
        $persona->setApellido($apellido);

        return $persona;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $persona = new self();
        $persona->id = (int) $row['id'];
        $persona->nombre = $row['nombre'];
        $persona->apellido = $row['apellido'];
        $persona->setTimestamps(
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            $row['deleted_at'] ?? null
        );

        return $persona;
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

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): void
    {
        $this->assertNotEmpty($apellido, 'apellido');
        $this->assertMaxLength($apellido, 100, 'apellido');
        $this->apellido = $apellido;
    }

    /**
     * Formato MARC21: "Apellido, Nombre"
     */
    public function getNombreCompleto(): string
    {
        return $this->apellido . ', ' . $this->nombre;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
