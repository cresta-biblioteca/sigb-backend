<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Models;

use App\Shared\Entity;

class Tema extends Entity
{
    private string $titulo;

    private function __construct()
    {
    }

    /**
     * Crea un nuevo Tema (valida datos)
     */
    public static function create(string $titulo): self
    {
        $tema = new self();
        $tema->setTitulo($titulo);

        return $tema;
    }

    /**
     * Reconstruye desde base de datos (sin validar)
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabase(array $row): self
    {
        $tema = new self();
        $tema->id = (int) $row['id'];
        $tema->titulo = $row['titulo'];

        return $tema;
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
