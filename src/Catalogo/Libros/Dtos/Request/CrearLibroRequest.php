<?php

declare(strict_types=1);

namespace App\Catalogo\Libros\Dtos\Request;

readonly class CrearLibroRequest
{
    public function __construct(
        private int $articuloId,
        private string $exportMarc,
        private ?string $isbn = null,
        private ?string $issn = null,
        private ?int $paginas = null,
        private ?string $autor = null,
        private ?string $autores = null,
        private ?string $colaboradores = null,
        private ?string $tituloInformativo = null,
        private ?int $cdu = null,
        private ?string $editorial = null,
        private ?string $lugarDePublicacion = null
    ) {
    }

    public function getArticuloId(): int
    {
        return $this->articuloId;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function getIssn(): ?string
    {
        return $this->issn;
    }

    public function getPaginas(): ?int
    {
        return $this->paginas;
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

    public function getEditorial(): ?string
    {
        return $this->editorial;
    }

    public function getLugarDePublicacion(): ?string
    {
        return $this->lugarDePublicacion;
    }
}
