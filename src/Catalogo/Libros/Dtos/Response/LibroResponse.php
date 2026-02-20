<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Response;

use DateTimeImmutable;

class LibroResponse
{
    public function __construct(
        public readonly int $articuloId,
        public readonly string $isbn,
        public readonly ?string $autor,
        public readonly ?string $autores,
        public readonly ?string $colaboradores,
        public readonly ?string $tituloInformativo,
        public readonly ?int $cdu,
        public readonly string $exportMarc,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt
    ) {
    }


    /**
     * Convierte el DTO a array
     */
    public function toArray(): array
    {
        return [
            'articulo_id' => $this->articuloId,
            'isbn' => $this->isbn,
            'autor' => $this->autor,
            'autores' => $this->autores,
            'colaboradores' => $this->colaboradores,
            'titulo_informativo' => $this->tituloInformativo,
            'cdu' => $this->cdu,
            'export_marc' => $this->exportMarc,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}