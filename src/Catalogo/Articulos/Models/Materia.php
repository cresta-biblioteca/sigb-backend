<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Models;

use App\Shared\Entity;

class Materia extends Entity
{
    private string $titulo;

    private function __construct()
    {
    }

    /**
     * Crea una nueva Materia (valida datos)
     */
    public static function create(string $titulo): self
    {
        $materia = new self();
        $materia->setTitulo($titulo);

        return $materia;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $materia = new self();
        $materia->id = (int) $row['id'];
        $materia->titulo = $row['titulo'];

        return $materia;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): void
    {
        $this->assertNotEmpty($titulo, 'titulo');
        $this->assertMaxLength($titulo, 100, 'titulo');
        $this->titulo = $titulo;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
        ];
    }
}
