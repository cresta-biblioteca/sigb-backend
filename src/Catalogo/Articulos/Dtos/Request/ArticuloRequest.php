<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

readonly class ArticuloRequest
{
    public function __construct(
        private string $titulo,
        private int $anioPublicacion,
        private string $tipo,
        private string $idioma = 'es',
        private ?string $descripcion = null
    ) {
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function getAnioPublicacion(): int
    {
        return $this->anioPublicacion;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getIdioma(): string
    {
        return $this->idioma;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }
}
