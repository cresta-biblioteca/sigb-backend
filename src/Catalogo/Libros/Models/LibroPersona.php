<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Models;

readonly class LibroPersona
{
    public function __construct(
        public Persona $persona,
        public string $rol,
        public int $orden = 0
    ) {
    }
}
