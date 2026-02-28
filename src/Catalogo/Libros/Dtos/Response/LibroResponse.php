<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Response;

use JsonSerializable;

readonly class LibroResponse implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $articuloId,
        public string $isbn,
        public ?string $autor,
        public ?string $autores,
        public ?string $colaboradores,
        public ?string $tituloInformativo,
        public ?int $cdu,
        public string $exportMarc,
        /** @var array<string, mixed>|null */
        public ?array $articulo = null
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'articulo_id' => $this->articuloId,
            'isbn' => $this->isbn,
            'autor' => $this->autor,
            'autores' => $this->autores,
            'colaboradores' => $this->colaboradores,
            'titulo_informativo' => $this->tituloInformativo,
            'cdu' => $this->cdu,
            'export_marc' => $this->exportMarc,
            'articulo' => $this->articulo,
        ];
    }
}