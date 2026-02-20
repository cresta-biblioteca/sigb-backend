<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

class LibroRequest
{
    public function __construct(
        public readonly int $articuloId,
        public readonly string $isbn,
        public readonly string $exportMarc,
        public readonly ?string $autor = null,
        public readonly ?string $autores = null,
        public readonly ?string $colaboradores = null,
        public readonly ?string $tituloInformativo = null,
        public readonly ?int $cdu = null
    ) {
    }

    /**
     * Crea un DTO a partir de datos de request
     */
    public static function fromArray(array $data): self
    {
        return new self(
            articuloId: (int) $data['articulo_id'],
            isbn: (string) $data['isbn'],
            exportMarc: (string) $data['export_marc'],
            autor: $data['autor'] ?? null,
            autores: $data['autores'] ?? null,
            colaboradores: $data['colaboradores'] ?? null,
            tituloInformativo: $data['titulo_informativo'] ?? null,
            cdu: isset($data['cdu']) ? (int) $data['cdu'] : null
        );
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
        ];
    }
}
