<?php

declare(strict_types=1);

namespace App\Lectores\Models;

use App\Shared\Entity;

class Carrera extends Entity
{
    private string $codigo;
    private string $nombre;

    private function __construct()
    {
    }

    /**
     * Crea una nueva Carrera (valida datos)
     */
    public static function create(string $codigo, string $nombre): self
    {
        $carrera = new self();
        $carrera->setCodigo($codigo);
        $carrera->setNombre($nombre);

        return $carrera;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $carrera = new self();
        $carrera->id = (int) $row['id'];
        $carrera->codigo = $row['codigo'];
        $carrera->nombre = $row['nombre'];

        return $carrera;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): void
    {
        $this->assertNotEmpty($codigo, 'codigo');
        $this->assertMaxLength($codigo, 3, 'codigo');
        $this->codigo = strtoupper($codigo);
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
        ];
    }
}
