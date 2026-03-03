<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

readonly class LibroRequest
{
    public function __construct(
        private int $articuloId,
        private string $isbn,
        private string $exportMarc,
        private ?string $autor = null,
        private ?string $autores = null,
        private ?string $colaboradores = null,
        private ?string $tituloInformativo = null,
        private ?int $cdu = null
    ) {
    }

    public function getArticuloId(): int
    {
        return $this->articuloId;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function getExportMarc(): string
    {
        return $this->exportMarc;
    }

    public function getAutor(): ?string
    {
        return $this->autor;
    }

    public function getAutores(): ?string
    {
        return $this->autores;
    }

    public function getColaboradores(): ?string
    {
        return $this->colaboradores;
    }

    public function getTituloInformativo(): ?string
    {
        return $this->tituloInformativo;
    }

    public function getCdu(): ?int
    {
        return $this->cdu;
    }
}
