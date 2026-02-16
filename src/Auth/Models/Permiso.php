<?php

declare(strict_types=1);

namespace App\Auth\Models;

use App\Shared\Entity;

class Permiso extends Entity
{
    private string $nombre;
    private string $descripcion;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Permiso (valida datos)
     */
    public static function create(string $nombre, string $descripcion): self
    {
        $permiso = new self();
        $permiso->setNombre($nombre);
        $permiso->setDescripcion($descripcion);

        return $permiso;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $permiso = new self();
        $permiso->id = (int) $row['id'];
        $permiso->nombre = $row['nombre'];
        $permiso->descripcion = $row['descripcion'];

        return $permiso;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->assertNotEmpty($nombre, 'nombre');
        $this->assertMaxLength($nombre, 255, 'nombre');
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
        ];
    }
}
