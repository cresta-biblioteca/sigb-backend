<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

readonly class ArticuloRequest
{
    public function __construct(
        public string $titulo,
        public int $anioPublicacion,
        public int $tipoDocumentoId,
        public string $idioma = 'es'
    ) {
    }
}
