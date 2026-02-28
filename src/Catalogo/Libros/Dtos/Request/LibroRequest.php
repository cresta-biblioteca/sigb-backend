<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

class LibroRequest
{
    public function __construct(
        public int $articuloId,
        public string $isbn,
        public string $exportMarc,
        public ?string $autor = null,
        public ?string $autores = null,
        public ?string $colaboradores = null,
        public ?string $tituloInformativo = null,
        public ?int $cdu = null
    ) {
    }
}
