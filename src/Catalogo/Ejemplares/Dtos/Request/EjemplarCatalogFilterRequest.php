<?php

declare(strict_types=1);

namespace App\Catalogo\Ejemplares\Dtos\Request;

class EjemplarCatalogFilterRequest
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public readonly array $filters
    ) {
    }
}
