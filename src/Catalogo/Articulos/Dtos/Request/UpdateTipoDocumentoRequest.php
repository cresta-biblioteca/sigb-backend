<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;

class UpdateTipoDocumentoRequest
{
    public ?string $codigo;
    public ?string $descripcion;
    public ?bool $renovable;
    public ?string $detalle;

    public function __construct(
        ?string $codigo = null,
        ?string $descripcion = null,
        ?bool $renovable = null,
        ?string $detalle = null
    ) {
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->renovable = $renovable;
        $this->detalle = $detalle;
    }
    public function setCodigo(?string $codigo): void
    {
        $this->codigo = $codigo;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setRenovable(?bool $renovable): void
    {
        $this->renovable = $renovable;
    }

    public function setDetalle(?string $detalle): void
    {
        $this->detalle = $detalle;
    }
}
