<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

class LibroCatalogFilterRequest
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public readonly array $filters,
        public readonly int $page,
        public readonly int $perPage
    ) {
    }
}
