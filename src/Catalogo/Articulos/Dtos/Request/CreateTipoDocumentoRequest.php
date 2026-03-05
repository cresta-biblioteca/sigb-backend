<?php

declare(strict_types=1);

namespace App\Catalogo\Articulos\Dtos\Request;


readonly class CreateTipoDocumentoRequest {
    public string $codigo;
    public string $descripcion;
    public bool $renovable;
    public ?string $detalle;

    public function __construct(string $codigo, string $descripcion, bool $renovable = true, ?string $detalle = null)
    {
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->renovable = $renovable;
        $this->detalle = $detalle ?? null;
    }
} 